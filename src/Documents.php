<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 7/24/18
 * Time: 8:22 PM
 */

namespace FS\Documents;

use FS\Documents\Helpers\ResponseHelper;
use FS\Documents\Helpers\S3;
use FS\Documents\Helpers\SimpleCache;
use League\Csv\CannotInsertRecord;
use League\Csv\Reader;
use League\Csv\Writer;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\PDO\Database;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Slim\PDO\Statement\DeleteStatement;
use Slim\PDO\Statement\SelectStatement;
use Slim\PDO\Statement\UpdateStatement;

class Documents
{
    use ResponseHelper;
    use S3;

    /** @var ContainerInterface  */
    protected $container;

    /** @var Database */
    protected $slimPdo;

    /** @var SimpleCache */
    protected $simpleCache;

    /**
     * Users constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;

        $this->simpleCache = new SimpleCache;

        try {
            $this->slimPdo = $this->container->get('pdo');

        } catch (NotFoundExceptionInterface $exception) {
            // @TODO return with Error!!!!!

        } catch (ContainerExceptionInterface $exception) {
            // @TODO return with Error!!!!!

        } catch (\Exception $exception) {
            // @TODO return with Error!!!!!
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function delete(Request $request, Response $response, array $args)
    {
        $affected_rows = [];
        $document_id = $request->getAttribute('id');
        $user = $this->getAuthUsername($request);

        if (!is_numeric($document_id) || empty($document_id)) {
            $this->setError('The ID must be an integer');

        } else {
            $document = $this->getOneDocument($document_id);
            if ($document['owner'] !== $user) {
                return $this
                    ->setError('404')
                    ->respondWithError($response);
            }
            // delete related data:
            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_dates`')
                ->where('`document_id`', '=', $document_id);

            $affected_rows['dates'] = $deleteStatement->execute();

            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_ints`')
                ->where('`document_id`', '=', $document_id);

            $affected_rows['ints'] = $deleteStatement->execute();

            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_strings`')
                ->where('`document_id`', '=', $document_id);

            $affected_rows['strings'] = $deleteStatement->execute();

            // delete document
            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`documents`')
                ->where('`id`', '=', $document_id);

            $affected_rows['documents'] = $deleteStatement->execute();

            // delete cache
            $this->simpleCache->remove('document-' . $document_id);
        }

        return $this
            ->setJson([
                'removed' => $affected_rows,
                'document_id' => $document_id
            ])
            ->makeJsonResponse($request, $response);
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function export(Request $request, Response $response, array $args)
    {
        $document_id = $request->getAttribute('id');
        $content = '';

        if (!is_numeric($document_id) || empty($document_id)) {
            $this->setError('The ID must be an integer');

        } else {
            $document = $this->getOneDocument($document_id);
            $user = $this->getAuthUsername($request);

            if ($document['owner'] !== $user) {
                return $this
                    ->setError('404')
                    ->respondWithError($response);
            }

            // build CSV:
            $file = $this->makeCSV($document_id, $document);

            if (!$file) {
                $this->setError('Could not create CSV');

            } else {
                $reader = Reader::createFromPath($this->getExportDirectory() . 'document-' . $document_id . '.csv', 'r');
                $content = $reader->getContent();
            }
        }

        // Send as download:
        return $this->downloadCsv($response, 'document-' . $document_id . '.csv', $content);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function exportToCloud(Request $request, Response $response, array $args)
    {
        $document_id = $request->getAttribute('id');
        $service = $request->getAttribute('service', 's3');

        if (!is_numeric($document_id) || empty($document_id)) {
            $this->setError('The ID must be an integer');

        } elseif ($service !== 's3') {
            $this->setError('Invalid Cloud Service');

        }  else {
            $document = $this->getOneDocument($document_id);
            $user = $this->getAuthUsername($request);

            if ($document['owner'] !== $user) {
                return $this
                    ->setError('404')
                    ->respondWithError($response);
            }

            // build CSV:
            $file = $this->makeCSV($document_id, $document);

            if (!$file) {
                $this->setError('Could not create CSV');

            } else {
                $s3_path = 'document-' . $document_id . '.csv';
                if ($this->transferFileToS3($s3_path, $file)) {
                    // Good for 30 minutes:
                    $temp_url = $this->getS3Url($s3_path);
                }
            }
        }

        return $this
            ->setJson([
                'csvUrl' => $temp_url,
                'urlLifeSpan' => '30 minutes',
                'document_id' => $document_id
            ])
            ->makeJsonResponse($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getMany(Request $request, Response $response, array $args)
    {
        $per_page = (int)$request->getParam('perPage', 15);
        $page = (int)$request->getParam('page', 1);

        $offset = ($page - 1) * $per_page;
        $user = $this->getAuthUsername($request);

        /** @var SelectStatement $selectStatement */
        $selectStatement = $this->slimPdo
            ->select()
            ->from('`documents`')
            ->where('`owner`', '=', $user)
            ->orderBy('`created`','DESC')
            ->limit($per_page, $offset);

        $stmt = $selectStatement->execute();

        $documents = [];

        while ($document = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data = $this->simpleCache->get('document-' . $document['id']);

            if (!$data) {
                $data = $document;
                $data['dates'] = $this->getDocumentDates($document['id']);
                $data['ints'] = $this->getDocumentInts($document['id']);
                $data['strings'] = $this->getDocumentStrings($document['id']);

                $this->simpleCache->set('document-' . $document['id'], $data);
            }

            $documents[] = $data;
        }

        /** @var SelectStatement $selectStatement */
        $totalStatement = $this->slimPdo
            ->select(['COUNT(*) AS `total`'])
            ->from('`documents`')
            ->where('`owner`', '=', $user)
            ->orderBy('`created`','DESC');

        $rs = $totalStatement->execute();
        $total = $rs->fetchColumn();

        return $this
            ->setJson([
                'total' => $total,
                'page' => $page,
                'perPage' => $per_page,
                'totalPages' => ceil($total / $per_page),
                'documents' => $documents
            ])
            ->makeJsonResponse($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function store(Request $request, Response $response, array $args)
    {
        $name = $request->getParam('name', false);
        if (empty($name)) {
            $this->setError('The name parameter is required');
        }

        $dates = (array)$request->getParam('dates', []);
        foreach ($dates as $key => $date) {
            if (!$this->validateDate($date)) {
                $this->setError('All values in the dates parameter must be in a valid MySQL date format, Y-m-d H:i:s');
            }
        }

        $ints = (array)$request->getParam('ints', []);
        foreach ($ints as $key => $int) {
            if (!is_numeric($int)) {
                $this->setError('All values in the ints parameter must be integers');
            }
        }

        $json = [];
        if (!$this->hasErrors()) {
            $user = $this->getAuthUsername($request);

            // save to the DB:
            $insertStatement = $this->slimPdo
                ->insert(['`name`', '`owner`'])
                ->into('documents')
                ->values([$name, $user]);

            $document_id = $insertStatement->execute();

            $json['document_id'] = $document_id;

            $this->saveDocumentDates($document_id, $dates);

            $this->saveDocumentInts($document_id, $ints);

            $strings = (array)$request->getParam('strings', []);
            $this->saveDocumentStrings($document_id, $strings);

        }

        return $this
            ->setJson($json)
            ->makeJsonResponse($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function update(Request $request, Response $response, array $args)
    {
        $json = [
            'success' => false
        ];
        $document_id = $request->getAttribute('id');

        if (!is_numeric($document_id) || empty($document_id)) {
            $this->setError('The ID must be an integer');

        } else {
            $document = $this->getOneDocument($document_id);

            if  (!$document) {
                $this->setError('The ID does not exist'); //should it store? or error?
                //return $this->store($request, $response, $args);
            }

            $user = $this->getAuthUsername($request);
            if ($document['owner'] !== $user) {
                return $this
                    ->setError('404')
                    ->respondWithError($response);
            }

            $name = $request->getParam('name', false);
            if (empty($name)) {
                $this->setError('The name parameter is required');
            }

            $dates = (array)$request->getParam('dates', []);
            foreach ($dates as $key => $date) {
                if (!$this->validateDate($date)) {
                    $this->setError('All values in the dates parameter must be in a valid MySQL date format, Y-m-d H:i:s');
                }
            }

            $ints = (array)$request->getParam('ints', []);
            foreach ($ints as $key => $int) {
                if (!is_numeric($int)) {
                    $this->setError('All values in the ints parameter must be integers');
                }
            }

            if (!$this->hasErrors()) {
                // save to the DB:
                /** @var UpdateStatement $updateStatement */
                $updateStatement = $this->slimPdo
                    ->update([
                        '`name`' => $name,
                        '`modified`' => date('Y-m-d H:i:s')
                    ])
                    ->table('documents')
                    ->where('`id`', '=', $document_id);

                $affected = $updateStatement->execute();

                if ( $this->slimPdo->errorCode() === '00000') {
                    $json['success'] = true;

                    $this->saveDocumentDates($document_id, $dates, $document['dates']);

                    $this->saveDocumentInts($document_id, $ints, $document['ints']);

                    $strings = (array)$request->getParam('strings', []);
                    $this->saveDocumentStrings($document_id, $strings, $document['strings']);

                    // delete cache
                    $this->simpleCache->remove('document-' . $document_id);

                } else {
                    $this->setError($this->slimPdo->errorInfo());
                }
            }
        }

        return $this
            ->setJson($json)
            ->makeJsonResponse($request, $response);
    }

    /**
     * @param int $document_id
     * @return array
     */
    protected function getDocumentDates(int $document_id)
    {
        $selectStatement = $this->slimPdo->select()
            ->from('document_dates')
            ->where('`document_id`', '=', $document_id)
            ->orderBy('`key`','ASC');

        $stmt = $selectStatement->execute();

        $dates = [];

        while ($document_dates = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $dates[$document_dates['key']] = $document_dates;
        }

        return $dates;
    }

    /**
     * @param int $document_id
     * @return array
     */
    protected function getDocumentInts(int $document_id)
    {
        $selectStatement = $this->slimPdo->select()
            ->from('document_ints')
            ->where('`document_id`', '=', $document_id)
            ->orderBy('`key`','ASC');

        $stmt = $selectStatement->execute();

        $ints = [];

        while ($document_ints = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ints[$document_ints['key']] = $document_ints;
        }

        return $ints;
    }

    /**
     * @param int $document_id
     * @return array
     */
    protected function getDocumentStrings(int $document_id)
    {
        $selectStatement = $this->slimPdo->select()
            ->from('document_strings')
            ->where('`document_id`', '=', $document_id)
            ->orderBy('`key`','ASC');

        $stmt = $selectStatement->execute();

        $strings = [];

        while ($document_strings = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $strings[$document_strings['key']] = $document_strings;
        }

        return $strings;
    }

    /**
     * @return string
     */
    protected function getExportDirectory()
    {
        $dir = __DIR__.'/export/';
        if (!file_exists(rtrim($dir, '/'))) {
            mkdir(rtrim($dir, '/'), '0700', true);
        }

        return $dir;
    }

    /**
     * @param int $id
     * @return array|bool|mixed
     */
    protected function getOneDocument(int $id)
    {
        $data = $this->simpleCache->get('document-' . $id);

        if (!$data) {
            /** @var SelectStatement $selectStatement */
            $selectStatement = $this->slimPdo
                ->select()
                ->from('documents')
                ->where('id', '=', $id);

            $stmt = $selectStatement->execute();
            $document = $stmt->fetch();

            if (is_array($document)) {
                $data = $document;
                $data['dates'] = $this->getDocumentDates($document['id']);
                $data['ints'] = $this->getDocumentInts($document['id']);
                $data['strings'] = $this->getDocumentStrings($document['id']);

                $this->simpleCache->set('document-' . $document['id'], $data);
            }
        }

        return $data;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function getAuthUsername(Request $request)
    {
        $server_params = $request->getServerParams();
        return $server_params['PHP_AUTH_USER'];
    }

    /**
     * @param int $document_id
     * @param array $document
     * @return bool|string
     */
    protected function makeCSV(int $document_id, array $document)
    {
        /**
         * Allows you to export stored document for download as a comma separated text file with
        columns being: “key” and “value”. It should also contain “creation date” and “last update date”
        in it’s first line, before headings and list of fields.
         */
        $file = $this->getExportDirectory() . 'document-' . $document_id . '.csv';

        try {
            /** @var Writer $writer */
            $writer = Writer::createFromPath($file, 'w+');

            $writer->insertOne(['creation date', $document['created'], 'last update date', $document['modified']]);

            // header:
            $writer->insertOne(['Key', 'Value']);

            foreach ($document['dates'] as $key => $date) {
                $writer->insertOne([$key, $date['value']]);
            }

            foreach ($document['ints'] as $key => $date) {
                $writer->insertOne([$key, $date['value']]);
            }

            foreach ($document['strings'] as $key => $date) {
                $writer->insertOne([$key, $date['value']]);
            }

        } catch (CannotInsertRecord $exception) {
            $exception->getRecord(); //returns [1, 2, 3]
            $this->setError($exception->getMessage());
            return false;
        }

        // update document export time
        $document['last_exported'] = date('Y-m-d H:i:s');

        /** @var UpdateStatement $updateStatement */
        $updateStatement = $this->slimPdo
            ->update([
                '`last_exported`' => $document['last_exported']
            ])
            ->table('documents')
            ->where('`id`', '=', $document_id);

        $affected = $updateStatement->execute();
        if (!$affected) {
            $this->setError($this->slimPdo->errorInfo());
            return false;
        }

        // update cache
        $this->simpleCache->set('document-' . $document_id, $document);

        return $file;
    }

    /**
     * @param int $document_id
     * @param array $input
     * @param array $existing
     */
    protected function saveDocumentDates(int $document_id, array $input, array $existing=[])
    {
        foreach ($input as $key => $date) {
            if (isset($existing[$key])) {
                // update:
                $updateStatement = $this->slimPdo
                    ->update([
                        '`key`' => $key,
                        '`value`' => $date
                    ])
                    ->table('document_dates')
                    ->where('`id`', '=', $existing[$key]['id']);

                $updateStatement->execute();
                unset($existing[$key]);

            } else {
                // new
                $insertStatement = $this->slimPdo
                    ->insert(['`document_id`', '`key`', '`value`'])
                    ->into('document_dates')
                    ->values([$document_id, $key, $date]);

                $insertStatement->execute(false);

            }
        }

        // now delete any remaining existing records:
        if (count($existing)) {

            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_dates`')
                ->where('`document_id`', '=', $document_id)
                ->whereIn('`key`', array_keys($existing));

            $affected_rows = $deleteStatement->execute();
        }
    }

    /**
     * @param int $document_id
     * @param array $input
     * @param array $existing
     */
    protected function saveDocumentInts(int $document_id, array $input, array $existing=[])
    {
        foreach ($input as $key => $int) {
            if (isset($existing[$key])) {
                // update:
                $updateStatement = $this->slimPdo
                    ->update([
                        '`key`' => $key,
                        '`value`' => $int
                    ])
                    ->table('document_ints')
                    ->where('`id`', '=', $existing[$key]['id']);


                $updateStatement->execute();
                unset($existing[$key]);

            } else {
                // new
                $insertStatement = $this->slimPdo
                    ->insert(['`document_id`', '`key`', '`value`'])
                    ->into('document_ints')
                    ->values([$document_id, $key, $int]);

                $insertStatement->execute(false);

            }
        }

        // now delete any remaining existing records:
        if (count($existing)) {

            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_ints`')
                ->where('`document_id`', '=', $document_id)
                ->whereIn('`key`', array_keys($existing));

            $affected_rows = $deleteStatement->execute();
        }
    }

    /**
     * @param int $document_id
     * @param array $input
     * @param array $existing
     */
    protected function saveDocumentStrings(int $document_id, array $input, array $existing=[])
    {
        foreach ($input as $key => $string) {
            if (isset($existing[$key])) {
                // update:
                $updateStatement = $this->slimPdo
                    ->update([
                        '`key`' => $key,
                        '`value`' => $string
                    ])
                    ->table('document_strings')
                    ->where('`id`', '=', $existing[$key]['id']);

                $updateStatement->execute();
                unset($existing[$key]);

            } else {
                // new
                $insertStatement = $this->slimPdo
                    ->insert(['`document_id`', '`key`', '`value`'])
                    ->into('document_strings')
                    ->values([$document_id, $key, $string]);

                $insertStatement->execute(false);

            }
        }

        // now delete any remaining existing records:
        if (count($existing)) {

            /** @var DeleteStatement $deleteStatement */
            $deleteStatement = $this->slimPdo
                ->delete()
                ->from('`document_strings`')
                ->where('`document_id`', '=', $document_id)
                ->whereIn('`key`', array_keys($existing));

            $affected_rows = $deleteStatement->execute();
        }
    }

    /**
     * @param string $date
     * @param string $format
     * @return bool
     */
    protected function validateDate($date, $format='Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        //echo PHP_EOL.' DateTime::'.$d->format($format);exit();
        return $d && $d->format($format) === $date;
    }
}