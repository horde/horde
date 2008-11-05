
-- save top
SET @tc = (SELECT
    count_news +
    count_galleries +
    count_classifieds +
    count_videos +
    count_attendances +
    count_wishes +
    count_blogs AS count
FROM
    folks_users
ORDER BY
    count DESC
LIMIT 1);

-- check

select @tc;

-- update

UPDATE folks_users SET activity = (
    count_news * 20 +
    count_galleries * 5 +
    count_classifieds * 5 +
    count_videos * 7 +
    count_attendances * 2 +
    count_wishes +
    count_blogs * 15)
/@tc