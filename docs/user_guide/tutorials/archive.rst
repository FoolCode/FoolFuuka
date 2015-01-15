Asagi
=====

Requirements
------------

* `Asagi`_
* `JDK 7`_
* Maven

Compiling Asagi
---------------

If you wish to compile `Asagi`_ manually, you will need to use `Maven`_ to create an executable `jar` container.

    Instructions:

    * `git clone https://github.com/oohnoitz/asagi.git`
    * Run the command `mvn package assembly:single` to generate a standalone executable jar

Configuring Asagi
-----------------

Asagi uses a JSON configuration file named `asagi.json`. An example configuration file is included in the git repository under the filename `asagi.json.example`.

    "default": {

        "path": "/var/www/asagi/boards/",
            `This is the absolute path to the directory where all media files are stored.`

        "useOldDirectoryStructure": false,
            `This setting should remain set to false.`

        "webserverGroup": "www-data",
            `This should be set to the same user running your webserver.`

        "thumbThreads": 5,
            `This is the number of threads per board that will be used to download thumbnails.`

        "mediaThreads": 5,
            `This is the number of threads per board that will be used to download full images.`

        "newThreadsThreads": 5,
            `This is the number of threads per board that will be used to parse threads.`

        "deletedThreadsThresholdPage": 8,
            `This is the threshold which is used to determine if the thread has been purge from 4chan by the OP or janitor.`

        "refreshDelay": 10,
            `This will set the interval used to check the API for all modified threads.`

        "throttleAPI": true,
            `This will cause the fetcher to throttle properly to match the rate-limit implemented.`

        "throttleURL": "api.4chan.org",
            `This is the hostname of the API/HTML being throttled.`

        "throttleMillisec": 1100,
            `This will set the interval used for throttling.`

        "threadRefreshRate": 3
            `This will set the interval used to forcibly refresh a thread.`
    },

    "<board>": {<settings>},
        `<board>` is the letter of the board you wish to archive.

        `<settings>` are the settings listed above set at a per-board level.

    "<board>": {<settings>}

Using Asagi
-----------

In order to run Asagi, you must have `JDK 7`_ or higher installed on the server. Furthermore, you must ensure that your JSON configuration file is properly configured as well. The following command is used to run `Asagi`_:

    `java -Xmx256m -XX:+UseParNewGC -XX:MaxPermSize=24m -jar asagi.jar`

*Note: It is recommended that `screen` or `tmux` be used with `Asagi`_.*

Configuring FoolFuuka
---------------------

After you have `Asagi`_ running within `screen` or `tmux`, it is time to confiugre FoolFuuka to interface with the archived boards and its contents. Furthermore, it is required that you run or restart `Asagi`_ with an updated `asagi.json` containing any new boards you wish to archive from 4chan before adding them to FoolFuuka.

* Access the Control Panel for FoolFuuka
* Navigate to `Boards > Preferences`
* Fill in the `Boards Database` field with the database name set in `asagi.json`
* Save your settings
* Navigate to `Boards > Add Board`
* Fill in the required fields and check the "Is this a 4chan archiving board?" checkbox
* Submit to create the board
* Repeat the last three steps until all archived boards are added

You should now be able to interact with the archived boards with FoolFuuka and browse the data.

*Warning: You must have `Asagi`_ do the initial table creation for each board before adding them to FoolFuuka*

.. _Asagi: https://github.com/FoolCode/asagi
