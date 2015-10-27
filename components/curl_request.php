<?php

    function curl_request( &$link, &$headers, $binary = false ){
        
            $loaded_data = null;
            
            $ch = curl_init($link);
            
            echo  curl_error($ch);

            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) Gecko/20100101 Firefox/16.0");
            curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
            curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
            curl_setopt($ch, CURLOPT_REFERER, 'http://www.kino-teatr.ru');
            
            echo  curl_error($ch);
            
            if( $binary ){
                
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);                                        
                curl_setopt($ch, CURLOPT_HEADER, false);
            }

            $loaded_data = curl_exec($ch);
            
            echo  curl_error($ch);
            
            curl_close($ch);
                    
            return $loaded_data;       

    }

