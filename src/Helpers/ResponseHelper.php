<?php

namespace FS\Documents\Helpers;

use Slim\Http\Request;
use Slim\Http\Response;


trait ResponseHelper
{
    protected $json;

    /** @var array  */
    protected $errors = [];

    /**
     * @return array
     */
    protected function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    protected function hasErrors()
    {
        if (count($this->getErrors())) {
            return true;
        }
        return false;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function setError($message)
    {
        $this->errors[] = $message;
        return $this;
    }

    /**
     * @param array $errors
     * @return $this
     */
    protected function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }


    /**
     * @return mixed
     */
    protected function getJson()
    {
        return $this->json;
    }

    /**
     * @param mixed $json
     * @return $this
     */
    protected function setJson($json)
    {
        $this->json = $json;
        return $this;
    }

    /**
     * @param Response $response
     * @param string $name
     * @param string $content
     *
     * @return Response|static
     */
    protected function downloadCsv(Response $response, $name, $content)
    {
        if ($this->hasErrors()) {
            return $this->respondWithError($response);
        }

        $response = $response
            ->withHeader('Content-type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Disposition', 'attachment; filename="'.$name.'"');

        $response->getBody()->write($content);
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response|static
     */
    protected function makeJsonResponse(Request $request, Response $response)
    {
        if ($this->hasErrors()) {
            return $this->respondWithError($response);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $body = json_encode($this->getJson());

        $response->getBody()->write($body);
        return $response;
    }

    /**
     * @param Response $response
     * @param $message
     *
     * @return Response|static
     */
    protected function respondWithError(Response $response)
    {
        // @TODO status codes
        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode(
            [
                'error' => true,
                'message' => $this->getErrors(),
                'success' => false
            ]
        ));

        return $response;
    }
}
