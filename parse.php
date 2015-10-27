<?php

    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);

    set_time_limit(0);
    ini_set('memory_limit', '1500M');
    
    
    set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/components');
    
    include 'curl_request.php';
    include 'simple_html_dom.php';
    include 'func.php';
     
    $forbidden_sites = [
                            "cccp-film.ru",
                            "www.1tv.ru",
                            "kino.fraia.ru",
                            "new-kino.net",
                            "kinobanda.net",
                            "www.needforvid.com",
                            "petro27.io.ua",
                            "russkie-serialy.net",
                            "filmodom.net",
                            "my.mail.ru",
                            "www.1plus1.ua",
                            "kinobar.net",
                            "www.ivi.ru",
                            "boomstarter.ru",
                            "banan.tv",
                            "www.tvcok.ru",
                            "filmix.net",
                            "seasonvar.ru",
                            "narkom.pro",
                            "multzona.org",
                            "likeinvest.org.kinoestet.ru",
                            "russia.tv",
                            "tfilm.tv",
                            "hot-film.tv",
                            'cinemas.su',
                            'zona.mobi',
                        ];
        
    $allowed_sites = array(
                                "kinopoisk.ru"      =>   0,
                                "ru.wikipedia.org"  =>   0,
            
                              );
    
    $stat = [];
    $domen_stat = [];
    $film_number = 0;
    $film_name = '';
    $series_number = null;
    $film_id = null;
    
    //$result = process_russia_tv( ['url' => 'http://russia.tv/video/show/brand_id/59019/episode_id/1170979/video_id/1133003'], 'div', 'id', 'p-pvideo-player', true);
    
    //var_dump($result);
    //http://russia.tv/video/show/brand_id/59019/episode_id/1170979/video_id/1133003
    
    //$result = process_russia_tv( ['url' => 'http://russia.tv/video/show/brand_id/6615/episode_id/1171007/video_id/1133091'], 'div', 'id', 'p-pvideo-player', true); 
    // $result = process_object( ['url' => 'http://multzona.org/6278-priklyucheniya-buratino.html' ], "div", '', "online", $class = true);
    //var_dump($result);
    
    //die();
    
    function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
    
        if (0 === error_reporting()) {
            return false;
        }
        
        file_put_contents( "errors.txt", "$errno: $errstr  ->  $errfile -> $errline\n\n" , FILE_APPEND );
        
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        
    }
    
    set_error_handler('handleError');
    
    //clear files
    
    file_put_contents( "log.txt", "");
    file_put_contents( "errors.txt", "");
    file_put_contents( "out.txt","");
    
     try {
        
        file_put_contents( "log.txt", " " . date("Y-m-d H:i:s", time()) . " Start parse video data \n\n" , FILE_APPEND );
        
        $dom = new simple_html_dom();   
        
        do_parse($dom);
        
        file_put_contents( "log.txt", " " . date("Y-m-d H:i:s", time()) . " End video parsing \n\n" , FILE_APPEND );
        
        
    } catch (ErrorException $e) {
            
            file_put_contents( "log.txt", "\n\n" . date("Y-m-d H:i:s", time()) . " Application crash  on film: {} \n\n" , FILE_APPEND );
            
    }
    
    
    function do_parse( &$dom ) {
        
        global  $film_name;
        global  $film_number;
        global $series_number;
        global $film_id;


        $portion_size = 1; //TODO: before run change to 10 or 20 or ...
        
        $db = mysql_connect('localhost', 'root',  '34dima');
        mysql_set_charset('utf8', $db);
        mysql_select_db('sovet_film');
        echo mysql_error();
        
        $result_query = mysql_query("Select count(*) From `films`");
        echo mysql_error();
         
        $count = mysql_fetch_assoc($result_query);
        $count = $count['count(*)'];
        
        $pages = ceil($count/$portion_size);
        
        var_dump($count, $pages);
        
          
        for( $i = 0; $i < $pages; $i++ ) {
            
            $films_query = mysql_query("Select * From `films` JOIN `film_year` on `films`.`id` = `film_year`.`film_id` JOIN `years` on `film_year`.`year_id` = `years`.`id` Limit  " . ($portion_size*$i) ."," .$portion_size."");
            echo mysql_error();
            
            $films = array();
           
            while( $film = mysql_fetch_assoc($films_query) ) {
                $films[] = $film;
            }
            
            
            foreach ( $films as $k => $film ) {
                
                $film_number++;
                $film_name = $film['name'];
                
                echo "\n {$film_number})   {$film_name}    " . date("d.M.Y H:i:s", time()) . "  -" . ($count-$film_number);
                
                if( $film['series'] >= 2 ) { // обработка посерийно если это сериал
                    
                    for( $i = 1 ; $i <= $film['series']; $i++ ) {
                        
                        $series_number = $i;
                        $film_id = $film['id'];
                        
                        process_film($film, $dom, $serial = true, $i);
                        
                        sleep( rand(7, 20) );
                        
                    }
                    
                    continue;
                    
                }
                
                $series_number = null;
                $film_id = $film['id'];
                
                process_film($film, $dom); //обработка односерийного фильма
                
                sleep( rand(7, 20) );
                
            }
        
        }
        
        
    }
    
    function process_film( $film, &$dom, $serial = false, $seria = null, $simple_search = false ) {
        
        $needle_url = "смотреть онлайн фильм {$film['name']}";
        
        // тонко настроить каким должен быть текст поискового запроса.
        
        if( $serial ) {
            
            $needle_url .= " серия $seria ";
            
        }
        
        //добавлять год очень ухудшает поиск а процент одинаковых названий фильмов невелик
                    
        if ( isset($film['year']) ) {
            
            $needle_url .= " {$film['year']} года";
            
        }
        
        //var_dump($needle_url);
        
        $needle = urlencode($needle_url);
        
        $link = '';
        
        if( $simple_search ) {
            
            $link = "https://www.google.com.ua/webhp?sourceid=chrome-instant&ion=1&espv=2&es_th=1&ie=UTF-8#q=$needle_url";
            
        } else {
            
            $link = "https://www.google.com.ua/search?q=$needle&espv=2&biw=1215&bih=915&tbm=vid&source=lnms";
            
        }
        
        $page = curl_request( $link, $headers);
        
        $headers = substr($page, 0, 15);

        preg_match('/.*(\d{3})/', $headers, $matches);

        if( $matches[1] != 200 ){

            file_put_contents( "out.txt", "\n\n" . date("Y-m-d H:i:s", time()) . "APP crush bad headers {$film["name"]}" , FILE_APPEND );
            die();

        }
        
        $sourses = [];
        
        $dom->load($page);
        
        
        $result_blocks = $dom->find('div.g'); // 'li.g'

        foreach( $result_blocks as $i => $block ) {
           
            $link = $block->find("h3.r a");
            
            preg_match('/.*?:\/\/(.*?)\/.*/', $link[0]->href, $matches);
            
            $sourses[$i]['domen'] = $matches[1];
            $sourses[$i]['url'] = $link[0]->href;

        }
        
        $sourses = filter_sourse($sourses, $serial, $seria);
        
        if( count($sourses) < 1 && $simple_search === true){
            
            return;
            
        }
        
        if( count($sourses) < 1 ) {
            
            sleep( rand(7, 20) );
            
            return process_film($film, $dom, $serial, $seria, $simple_search = true );
            
        }
        
        take_video_from_site( $sourses );
        
    }
    
    function filter_sourse( $sourses, $serial, $seria ) {
        
        global $forbidden_sites;
        
        //delete forbidden from list
        foreach ($sourses as $key => $src) {
            
            if( in_array($src['domen'], $forbidden_sites)) {
                unset($sourses[$key]);
            }
            
        }
        
        // delete dublications
        $sourses = array_values($sourses);
        
        $size = count($sourses);
        
        for( $i = 0; $i < $size; $i++) {
            
            $pattern = '';
            
            if( isset($sourses[$i]) ) {
                
                $pattern =  $sourses[$i];
                
            }else{
                
                continue;
            }
            
            for( $j = $i+1; $j < $size; $j++ ) {
                
                $compared = '';
                
                if( isset($sourses[$j]) ) {
                
                    $compared =  $sourses[$j];
                
                }else{
                
                    continue;
                }
                
                if( $pattern['domen'] == $compared['domen'] ){
                    
                    unset( $sourses[$j] );
                    
                }
                
                
            }
            
        }
        // end deleting dublications
        
        // delete if non serial fragmented
        
        if( !$serial ) {
            
            $sourses = array_values($sourses);
            
            foreach ($sourses as $key => $source ) {
                
                if( preg_match('/seriya|serija/', $source['url'], $matches ) ) {
                    
                    unset( $sourses[$key] );
                    
                }
                
            }
            
        }

        
        return array_values($sourses);
        
    }
    
    function take_video_from_site($sourses) {
        
        $film_stat = [];
        global $stat;
        global $domen_stat;
        global $film_number;
        global $film_name;
        global $series_number;
        global $film_id;
        
        $limit = 2;
        
            
        for( $i = 0; $i<$limit; $i++) {
            
            $link = '';
            
            if( !isset($sourses[$i]) ) {
                
                break;
                
            }
            
            //var_dump($sourses[$i]['url']);
                        
            $result = null;
            
            switch ($sourses[$i]['domen']){
                
                case 'www.youtube.com': $result = process_youtube($sourses[$i]);    break;
                case 'rutube.ru':       $result = process_rutube( $sourses[$i]);    break;
                case 'vkino.net':       $result = process_vkino( $sourses[$i]);     break;
                case 'uakino.net':      $result = process_vkino( $sourses[$i]);     break;
                case 'vidashki.ru':     $result = process_iframe( $sourses[$i], 'div', 'id', 'video');          break;
                case 'megogo.net':      $result = process_megogo( $sourses[$i], 'div', 'id', 'playerPlace');    break;
                case 'namba.kg':        $result = process_object( $sourses[$i], "div", $propery="", "movie-player", $class = true ); break;
                case 'film-ussr.ru':    $result = process_iframe( $sourses[$i], 'div', 'id', 'Playerholder');   break;
                case 'hdrezka.tv':      $result = process_iframe( $sourses[$i], 'div', 'id', 'videoplayer');    break;                
            
                default :    
                    
                            if( !isset( $domen_stat[$sourses[$i]['domen']] ) ) {
                            
                                $domen_stat[$sourses[$i]['domen']] = $sourses[$i];
                                file_put_contents('domens.txt', print_r($domen_stat, 1));
                            
                            }
                    
                          break;
            }
            
            if( isset( $stat[$sourses[$i]['domen']] )) {
                
                $stat[$sourses[$i]['domen']]['count']++;
                
            }else{
                
                $embed = ( isset( $result["embed"] ) ) ? $result["embed"] : null;
                $stat[ $sourses[ $i ][ 'domen' ] ]  = ['count' => 0, "embed" => $embed ];
                
            }
            
            file_put_contents("stat.txt", print_r($stat, 1));
            
            if( $result == null ) {
                
                $limit++;
                continue;
                
            }
        
            db_insert( $result );
            
            //var_dump("\nTODO: fill stat $-film_stat"); //количество найденых источников итд

            
        }
        
        
    }
    

    
    
    function db_insert( $insert_data ) {
        
        global $series_number;
        global $film_id;
        
        var_dump($insert_data['embed'], $series_number, $film_id );
        
        //var_dump(" \nTODO: make db insert");
        
        
    }
    
    