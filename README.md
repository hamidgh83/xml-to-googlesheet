## About The App
This is a console application to convert an XML file contents into google spreadsheet using google api. 


## Requirements
This project uses:
- PHP8.1+

The application has been set up with docker and you need to install:
- docker
- docker-compose

on your machine to run the project.

## How to Install
In order to run the application you have to build the docker image and run the container:

```bash
docker-compose up --build -d
```

Then run the comand bellow to install composer packages:

```bash
docker exec xml-to-googlesheet composer install
```
After installing composer packages you should configure the environment variables and add your google authentication parameters. See how to [create access credentions](https://developers.google.com/workspace/guides/create-credentials#service-account) in your google account.

## Running console command
Before you try to run the command, you must create an empty google spreadsheet and share it with your service account email. Then copy the spreadsheet ID from the URL and run:

```bash
docker exec xml-to-googlesheet bin/console xml:google:spreadshee <GOOGLESHEET_ID>
```

## Running Tests
To run the tests run:
```bash
docker-compose exec app bin/phpunit
```