## Document App

PHP >= 7.0 Allows you to store a set of key/value pairs of different types (strings, numbers, dates) as documents

1. [Slim](https://www.slimframework.com/)
2. [Slim/PDO](https://github.com/FaaPz/Slim-PDO)
3. [PHP League CSV](https://csv.thephpleague.com/)
4. [PHP League Flysystem](https://flysystem.thephpleague.com/docs/)
5. [vlucas/phpdotenv](https://packagist.org/packages/vlucas/phpdotenv)
6. [tuupola/slim-basic-auth](https://appelsiini.net/projects/slim-basic-auth/)
7. PHPUnit - no tests have been written 

## Auth 

Using basic auth, with two users account, see public/index.php for users

## REST routes

```
GET /documents
    Will get all documents that a use can see
    Example: http://192.168.33.10/FS-Documents/public/documents?perPage=50&page=1
GET /document/{id}/export 
    Download CSV
    Example: http://192.168.33.10/FS-Documents/public/document/13/export
    Future: GET /document/{id}/export/{type}, allow to define what the file type is and can accept date input
GET /document/{id}/export/{type}/{service} 
    Transfer file to AWS S3, ect.
    http://192.168.33.10/FS-Documents/public/document/14/export/csv/s3
POST /document
    Store new document
    Params: name, strings[key]=value,ints[key]=value,dates[key]=value
    Example: http://192.168.33.10/FS-Documents/public/document?name=Test 1&ints[myKey]=1234&ints[myKey2]=5678&dates[myDate]=1901-01-01 18:22:11&strings[myString]=This is only a test...
PUT /document/{id}
    Update existing document
    Example: http://192.168.33.10/FS-Documents/public/document/5?name=Test 1, Update+&ints[myKey]=12349&ints[myKey3]=9012&dates[myDate]=1991-01-01 18:22:11&strings[myString]=This is only a test Update 3...
DELETE /document/{id}
    Example: http://192.168.33.10/FS-Documents/public/document/10
```

## DB Modal

Keys are unique per document.

- documents - id, name, created, modified, last_exported, owner
- document_strings - id, document_id, key(varchar 32), value(medium text)
- document_int - id, document_id, key(varchar 32), value(int)
- document_date - id, document_id, key (varchar 32), value(date)

## S3

Pick your region: https://docs.aws.amazon.com/general/latest/gr/rande.html

## Setup 

1. Set up Virtual or Server with PHP >= 7.0 and clone or download files
2. Look in the src/SQL directory, you can load up an empty DB or with some data. Run the one SQL file
3. Copy the .env.example to just .env and fill in correct values
4. I used Nginx on [Scotchbox](https://box.scotch.io/) for my local, so I set the rules in the default config to point 
to the public/index.php file. [Slim webserver help](https://www.slimframework.com/docs/v3/start/web-servers.html)
  - Example rule to add after server_name:
```
  # Slim API
     location /FS-Documents/public/ {
         allow all;
         try_files $uri $uri/ /FS-Documents/public/index.php;
     }
     
     # Block all other trafic to slim code
     location ^~ /FS-Documents/ {
         deny all;
     }
     # End Slim API
 ```
5. Test Routes in [Postman](https://www.getpostman.com/), see the public/index.php file for the 2 test users