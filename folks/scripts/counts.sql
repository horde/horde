-- cron to update user content counters, update to you needs

-- classified
UPDATE folks_users SET count_classifieds = 0;
UPDATE folks_users SET count_classifieds = (
    SELECT COUNT(*) FROM classified_ads WHERE
        classified_ads.ad_active = 4 AND
        classified_ads.ad_validto < UNIX_TIMESTAMP() AND
        folks_users.user_uid = classified_ads.user_uid
);

-- news
UPDATE folks_users SET count_news = 0;
UPDATE folks_users SET count_news = (
    SELECT COUNT(*) FROM news WHERE
        news.status = 1 AND
        news.publish < NOW() AND
        folks_users.user_uid = news.user
);

-- video
UPDATE folks_users SET count_videos = 0;
UPDATE folks_users SET count_videos = (
    SELECT COUNT(*) FROM oscar_videos WHERE
        oscar_videos.video_status = 6 AND
        folks_users.user_uid = oscar_videos.video_user
);

-- attendances
UPDATE folks_users SET count_attendances = 0;
UPDATE folks_users SET count_attendances = (
    SELECT COUNT(*) FROM schedul, schedul_attendance WHERE
        schedul.ondate >= NOW() AND
        schedul_attendance.schedul_id = schedul.id AND
        folks_users.user_uid = schedul_attendance.user_id
);

-- wishes
UPDATE folks_users SET count_wishes = 0;
UPDATE folks_users SET count_wishes = (
    SELECT COUNT(*) FROM genie_wishes WHERE
        wish_purchased = 1 AND
        folks_users.user_uid = genie_wishes.wish_owner
);

-- galleries
UPDATE folks_users SET count_galleries = 0;
UPDATE folks_users SET count_galleries = (
    SELECT COUNT(*) FROM ansel_shares WHERE
        ansel_shares.attribute_images > 0 AND
        folks_users.user_uid = ansel_shares.share_owner
);

-- blogs
UPDATE folks_users SET count_blogs = 0;
UPDATE folks_users SET count_blogs = (
    SELECT COUNT(*) FROM thomas_blogs WHERE
         thomas_blogs.status = 1 AND
         folks_users.user_uid = thomas_blogs.user_uid
);
