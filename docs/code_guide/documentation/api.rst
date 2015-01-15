REST API
========


Index
-----

GET ``/_/api/chan/index/``

+---------------+-----------+----------------------------------------------------------+--------------+
| **Property**  | **Type**  | **Description**                                          | **Required** |
+===============+===========+==========================================================+==============+
| board         | `string`  | This is the shortname for the board.                     | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+
| page          | `integer` | The page number of the index.                            | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+

.. code-block:: json

    {
      "2": {
        "omitted": 10,
        "images_omitted": 10,
        "op": {
          "<POST OBJECT>"
        },
        "posts": [
          {
            "<POST OBJECT>"
          }
        ]
      },
      "1": {
        "omitted": 10,
        "images_omitted": 10,
        "op": {
          "<POST OBJECT>"
        },
        "posts": [
          {
            "<POST OBJECT>"
          }
        ]
      }
    }


Search
------

GET ``/_/api/chan/search/``

+---------------+-----------+----------------------------------------------------------+--------------+
| **Property**  | **Type**  | **Description**                                          | **Required** |
+===============+===========+==========================================================+==============+
| board         | `mixed`   | This is the shortname for the board.                     | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+
| email         | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| username      | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| tripcode      | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| capcode       | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| subject       | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| text          | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| filename      | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| filehash      | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| deleted       | `integer` |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| ghost         | `integer` |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| filter        | `integer` |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| date_start    | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| date_end      | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| order         | `string`  |                                                          | N            |
+---------------+-----------+----------------------------------------------------------+--------------+

.. code-block:: json

    [
      {
        "posts": [
          {
            "<POST OBJECT>"
          },
          {
            "<POST OBJECT>"
          }
        ]
      }
    ]


Thread
------

GET ``/_/api/chan/thread/?board=dev&num=1``

+---------------+-----------+----------------------------------------------------------+--------------+
| **Property**  | **Type**  | **Description**                                          | **Required** |
+===============+===========+==========================================================+==============+
| board         | `string`  | This is the shortname for the board.                     | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+
| num           | `integer` | This is the post number of the thread.                   | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+
| latest_doc_id | `integer` | This is the latest `doc_id` used as a starting point.    | N            |
+---------------+-----------+----------------------------------------------------------+--------------+
| last_limit    | `integer` | This limits the results to the last `x` posts.           | N            |
+---------------+-----------+----------------------------------------------------------+--------------+

.. code-block:: json

    {
      "1": {
        "op": {
          "<POST OBJECT>"
        },
        "posts": {
          "2": {
            "<POST OBJECT>"
          },
          "3": {
            "<POST OBJECT>"
          }
        }
      }
    }


Post
----

GET ``/_/api/chan/post/?board=dev&num=1``

+---------------+-----------+----------------------------------------------------------+--------------+
| **Property**  | **Type**  | **Description**                                          | **Required** |
+===============+===========+==========================================================+==============+
| board         | `string`  | This is the shortname for the board.                     | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+
| num           | `mixed`   | This is the post number.                                 | Y            |
+---------------+-----------+----------------------------------------------------------+--------------+

.. code-block:: json

    {
      "doc_id": "1",
      "poster_ip": "1111111111",
      "num": "1",
      "subnum": "0",
      "thread_num": "1",
      "op": "1",
      "timestamp": "1339024666",
      "timestamp_expired": "0",
      "capcode": "A",
      "email": null,
      "name": "Anonymous",
      "trip": null,
      "title": null,
      "comment": "COMMENT DATA HERE",
      "poster_hash": "fUSBgQ2y",
      "poster_country": null,
      "deleted": "0",
      "sticky": "0",
      "comment_processed": "COMMENT DATA HERE",
      "title_processed": "",
      "name_processed": "Anonymous",
      "email_processed": "",
      "trip_processed": "",
      "poster_hash_processed": "fUSBgQ2y",
      "fourchan_date": "6\/6\/12(Wed)23:17",
      "comment_sanitized": "COMMENT DATA HERE",
      "poster_country_name_processed": null,
      "media": {
        "op": "1",
        "media_id": "1024",
        "spoiler": "0",
        "preview_orig": "13390246665411s.jpg",
        "preview_w": "216",
        "preview_h": "250",
        "media_filename": "8211205.jpg",
        "media_w": "742",
        "media_h": "860",
        "media_size": "130990",
        "media_hash": "P2asAleYuUWVvEFBotaaxA==",
        "media_orig": "13390246665411.jpg",
        "exif": null,
        "total": "1",
        "banned": "0",
        "media": "13390246665411.jpg",
        "preview_op": "13390246665411s.jpg",
        "preview_reply": null,
        "media_status": "normal",
        "safe_media_hash": "P2asAleYuUWVvEFBotaaxA",
        "preview_orig_processed": "13390246665411s.jpg",
        "media_filename_processed": "8211205.jpg",
        "media_hash_processed": "P2asAleYuUWVvEFBotaaxA==",
        "media_link": "https:\/\/0-media-cdn.foolz.us\/ffuuka\/board\/dev\/image\/1339\/02\/13390246665411.jpg",
        "remote_media_link": "https:\/\/0-media-cdn.foolz.us\/ffuuka\/board\/dev\/image\/1339\/02\/13390246665411.jpg",
        "thumb_link": "https:\/\/0-media-cdn.foolz.us\/ffuuka\/board\/dev\/thumb\/1339\/02\/13390246665411s.jpg"
      }
    }
