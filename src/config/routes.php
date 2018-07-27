<?php

/** @var \Slim\App $app */

// Documents
$app->get('/documents', \FS\Documents\Documents::class .':getMany');

$app->post('/document', \FS\Documents\Documents::class .':store');

/**
 * Update should pass all params, if for example a document_int with the key of credit exists
 *  but is not passed it will be deleted
 */
$app->put('/document/{id}', \FS\Documents\Documents::class .':update');

$app->delete('/document/{id}', \FS\Documents\Documents::class .':delete');

// Export, csv
$app->get('/document/{id}/export', \FS\Documents\Documents::class .':export');

// allowing future types to be in the route, method is not defined
$app->get('/document/{id}/export/{type}', \FS\Documents\Documents::class .':exportType');

// pick cloud service
$app->get('/document/{id}/export/{type}/{service}', \FS\Documents\Documents::class .':exportToCloud');