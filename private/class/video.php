<?php

namespace rePok {
    class Videos
    {
        // this is like that so that it stays readable in the code and doesn't introduce a fucking huge horizontal scrollbar on GitHub. -grkb 3/31/2022
        //why the hell is it using tutorial names?
        public static $recommendedfields = "
		jaccard.video_id,
		jaccard.flags,
		jaccard.intersect,
		jaccard.union,
		jaccard.intersect / jaccard.union AS 'jaccard index'
	FROM
		(
		SELECT
			c2.video_id AS video_id,
			c2.flags AS flags,
			COUNT(ct2.tag_id) AS 'intersect',
			(
			SELECT
				COUNT(DISTINCT ct3.tag_id)
			FROM
				tag_index ct3
			WHERE
				ct3.video_id IN(c1.id, c2.id)
		) AS 'union'
	FROM
		videos AS c1
	INNER JOIN videos AS c2
	ON
		c1.id != c2.id
	LEFT JOIN tag_index AS ct1
	ON
		ct1.video_id = c1.id
	LEFT JOIN tag_index AS ct2
	ON
		ct2.video_id = c2.id AND ct1.tag_id = ct2.tag_id
	WHERE
		c1.id = ?
	GROUP BY
		c1.id,
		c2.id
	) AS jaccard
	WHERE
		jaccard.flags != 0x2
	ORDER BY
		jaccard.intersect / jaccard.union
	DESC";

        static function videofields(): string
        {
            return 'v.id, v.video_id, v.title, v.description, v.time, 
            (SELECT COUNT(*) FROM views WHERE video_id = v.video_id) AS views, 
            (SELECT COUNT(*) FROM comments WHERE id = v.video_id) AS comments, 
            (SELECT COUNT(*) FROM favorites WHERE video_id = v.video_id) AS favorites,
            v.flags, v.originalfile, v.videolength, v.category_id, v.author';
        }

        /**
         * Return a list of videos, Limit and order is required.
         *
         * @param string $orderBy A column in the videos table.
         * @param int $limit The limit.
         * @param string $whereSomething Precise what column.
         * @param string $whereEquals Precise the value of the column.
         * @return array A video list, ordered by what $orderBy specified.
         */
        static function getVideos($orderBy, $limit, $whereSomething = null, $whereEquals = null, $onlyProcessed = true): array
        {
            global $userfields, $videofields, $sql;
            if ($onlyProcessed) {
                if (isset($whereSomething)) {
                    $thing = "AND flags != 0x2";
                } else {
                    $thing = "WHERE flags != 0x2";
                }
            } else {
                $thing = null;
            }
            if (isset($whereSomething)) {
                $videoList = $sql->fetchArray($sql->query("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? $thing ORDER BY $orderBy LIMIT $limit", [$whereEquals]));
            } else {
                $videoList = $sql->fetchArray($sql->query("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id $thing ORDER BY $orderBy LIMIT $limit"));
            }
            foreach ($videoList as &$video) {
                $video['tags'] = VideoTags::getVideoTags($video['id']);
            }
            return $videoList;
        }

        /**
         * Return a list of videos that are simillar to the video the user is watching.
         *
         * @param string $videoID The ID of the currently watched video.
         * @return array A video list, ordered by "relevancy".
         */

        static function getRecommended($videoID): array
        {
            global $userfields, $videofields, $recommendedfields, $sql;
            $recommendfields = self::$recommendedfields;
            $intID = self::getVideoIntID($videoID);
            $recommendedList = $sql->fetchArray($sql->query("SELECT $recommendfields LIMIT 20", [$intID]));
            $videoList = array();
            foreach ($recommendedList as $row) {
                //$videoData = fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE v.video_id = ?", [$row['video_id']]);
                $videoData = self::fetchVideos("v.video_id", $row['video_id']);
                array_push($videoList, $videoData);
            }
            return $videoList;
        }

        /**
         * Return the interger ID of a video.
         *
         * @param string $video The randomized video ID.
         * @return int the ID of a video.
         */
        static function getVideoIntID($video): int
        {
            global $sql;
            return $sql->result("SELECT id FROM videos WHERE video_id = ?", [$video]);
        }

        /**
         * Return the randomized ID of a video.
         *
         * @param string $video The randomized video ID.
         * @return string the ID of a video.
         */
        static function getVideoRanID($video): string
        {
            global $sql;
            return $sql->result("SELECT video_id FROM videos WHERE id = ?", [$video]);
        }

        /**
         * Return a list of videos in an alternative way.
         *
         * @param string $whereSomething Precise what column.
         * @param string $whereEquals Precise the value of the column.
         * @return array A video list, ordered by what $orderBy specified.
         */
        static function fetchVideos($whereSomething, $whereEquals, $orderBy = null, $limit = null)
        {
            global $userfields, $videofields, $sql;
            if (isset($orderBy, $limit)) {
                $videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? AND flags != 0x2 ORDER BY $orderBy LIMIT $limit", [$whereEquals]);
            } elseif (isset($orderBy)) {
                $videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? AND flags != 0x2 ORDER BY $orderBy", [$whereEquals]);
            } elseif (isset($limit)) {
                $videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? AND flags != 0x2 LIMIT $limit", [$whereEquals]);
            } else {
                $videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? AND flags != 0x2", [$whereEquals]);
            }
            return $videoList;
        }

        static function getLatestVideo($userID)
        {
            $video = Videos::fetchVideos("author", $userID, "v.id DESC", 1);
            return $video;
        }

        /**
         * Return a list of videos that are simillar to the video the user is watching.
         *
         * @param string $videoID The ID of the currently watched video.
         * @return int Number of every single recommended video, goes further than 20 if there are more than 20 recommended videos.
         */
        static function countRecommended($videoID): int
        {
            global $userfields, $videofields, $recommendedfields, $sql;
            $recommendfields = self::$recommendedfields; //the fuck? -grkb 4/4/2022
            $intID = self::getVideoIntID($videoID);
            $recommendedList = $sql->fetch("SELECT COUNT(jaccard.video_id), $recommendfields", [$intID]) ['COUNT(jaccard.video_id)']; // FIXME: don't do the ordering shit, also does it count all uploaded videos or just the relevant ones -grkb 3/31/2022.
            return $recommendedList;
        }

        /**
         * Return the link to the FLV version of the video.
         *
         * @param string $videoID The ID of the currently watched video.
         * @return string A link to the FLV version of the video, or if nothing is inputted, an error.
         */
        static function getFlashVideo($videoID): string
        {
            if ($videoID ?? null) {
                $file = "dynamic/videos/" . $videoID . ".flv";
                return $file;
            } else {
                die("getFlashVideo Error: videoID is missing!");
            }
        }

        static function addVideo($new, $title, $description, $id, string $upload_file, string $og_file): void
        {
            global $sql;
            $sql->query("INSERT INTO videos (video_id, title, description, author, time, most_recent_view,
                    videofile, originalfile, flags) VALUES (?,?,?,?,?,?,?,?,?)",
                [$new, $title, $description, $id, time(), time(), $upload_file, $og_file, 0x2]);
        }

        static function bumpVideo(int $currentTime, $id): void
        {
            global $sql;
            $sql->query("UPDATE videos SET most_recent_view = ? WHERE video_id = ?", [$currentTime, $id]);
        }

        static function getVideoData($userfields, $id)
        {
            global $sql;
            $videoData = $sql->fetch("
        SELECT $userfields v.*,
            (SELECT COUNT(video_id) FROM views WHERE video_id = v.video_id) AS views,
            (SELECT COUNT(id) FROM comments WHERE video_id = v.video_id) AS comments
        FROM videos v
        JOIN users u ON v.author = u.id
        WHERE v.video_id = ?", [$id]);

            if (!$videoData) {
                error('404', "The video you were looking for cannot be found.");
            }

            return $videoData;
        }

        static function getFavoritedVideosFromUser(string $limit, $id): array
        {
            global $sql, $userfields, $videofields;
            $videoData = $sql->query("
SELECT $userfields $videofields FROM videos v 
	JOIN users u ON v.author = u.id 
	JOIN favorites f ON (f.video_id = v.video_id) 
	WHERE 
		f.user_id = ?
	AND 
		flags != 0x2
ORDER BY v.time DESC $limit", [$id]);
            $videos = $sql->fetchArray($videoData);
            foreach ($videos as &$video) {
                $video['tags'] = VideoTags::getVideoTags($video['id']);
            }
            return $videos;
        }
    }
}