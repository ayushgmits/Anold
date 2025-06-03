<?php

class ST_Api_Route_Controller
{

    function __construct()
    {
        $this->include_routes();
    }


    public function include_routes()
    {
        //User Api
        require_once  dirname(__FILE__) . '/user-routes/class-streamit-api-user-callback.php';
        require_once  dirname(__FILE__) . '/user-routes/class-streamit-api-user-routes.php';

        //Wppost Api
        require_once  dirname(__FILE__) . '/wpposts-routes/class-streamit-api-wppost-callback.php';
        require_once  dirname(__FILE__) . '/wpposts-routes/class-streamit-api-wppost-routes.php';

        //Streamit Api
        require_once  dirname(__FILE__) . '/streamit-routes/class-streamit-api-streamit-callback.php';
        require_once  dirname(__FILE__) . '/streamit-routes/class-streamit-api-streamit-routes.php';

        //Movie Api
        require_once  dirname(__FILE__) . '/movies-routes/class-streamit-api-movie-callback.php';
        require_once  dirname(__FILE__) . '/movies-routes/class-streamit-api-movie-routes.php';

        //Video Api
        require_once  dirname(__FILE__) . '/videos-routes/class-streamit-api-video-callback.php';
        require_once  dirname(__FILE__) . '/videos-routes/class-streamit-api-video-routes.php';

        //TVShow  Api
        require_once  dirname(__FILE__) . '/tvshow-routes/class-streamit-api-tvshow-callback.php';
        require_once  dirname(__FILE__) . '/tvshow-routes/class-streamit-api-tvshow-routes.php';

        //Episode  Api
        require_once  dirname(__FILE__) . '/episode-routes/class-streamit-api-episode-callback.php';
        require_once  dirname(__FILE__) . '/episode-routes/class-streamit-api-episode-routes.php';

        //Cast Api
        require_once  dirname(__FILE__) . '/cast-routes/class-streamit-api-cast-callback.php';
        require_once  dirname(__FILE__) . '/cast-routes/class-streamit-api-cast-routes.php';

        //Playlist Api
        require_once  dirname(__FILE__) . '/playlist-routes/class-streamit-api-playlist-callback.php';
        require_once  dirname(__FILE__) . '/playlist-routes/class-streamit-api-playlist-routes.php';
    }
}

new ST_Api_Route_Controller();
