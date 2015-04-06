# aws-swf-php-history-mocker
Build/mock workflow history to aid in testing with Amazon Web Service's Simple Workflow

Purpose
===
[Amazon Simple Workflow Service (SWF)] (http://aws.amazon.com/swf/)'s workflows contain event histories that describe everything that has occurred as part of the workflow execution.

To assist in testing your workflows, this project assists you by helping you mock up events and entire workflows so you can test how your code reacts, before you run actual workflows.


Features
===
* Import actual event history from Amazon Simple Workflow Service (SWF).
* Add desired events to existing history (or none at all), complete with logical connections to previous events.
* Exceptions to help prevent you from creating a workflow that SWF itself cannot create.

Install using composer
===
Add the following to your composer.json file.

    "require-dev": {
        "warenzema/aws-swf-php-history-mocker": "*"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/warenzema/aws-swf-php-history-mocker"
        }
    ]

This project will be added to Packagist when it is deemed sufficient stable.

Future
===
This project is still in beta, and is intended to eventually be used in conjunction another project (future) that will help developers manage their workflows when working with Decision Tasks.
