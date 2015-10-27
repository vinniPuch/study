<?php

    function process_youtube( $data ){

       $parsed_url = parse_url($data['url']);

       $code_id = explode('=', $parsed_url['query']);

       $pattern = '<iframe width="640" height="390" src="https://www.youtube.com/embed/'. $code_id[1] .'" frameborder="0" allowfullscreen></iframe>';

       $result = [ 'data' => $data, 'embed' => $pattern ];

       return $result;

    }

    function process_rutube( $data ) {

       $headers = '';

       $page = curl_request( $data['url'], $headers);
       $dom = new simple_html_dom();
       $dom->load($page);

       $iframe = $dom->find('textarea[id=embed-field]');

       if( !isset($iframe[0]->innertext)) {

           return null;

       }

       preg_match("/.*?src=\"(.*?)\".*/", $iframe[0]->innertext, $matches);

       $pattern = '<iframe width="640" height="390" src="'. $matches[1] .'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen </iframe>';

       $result = [ 'data' => $data, 'embed' => $pattern ];

       return $result;

    }

    function process_vkino( $data ){

       $headers = '';

       $page = curl_request( $data['url'], $headers);
       $dom = new simple_html_dom();
       $dom->load($page);

       $iframe = $dom->find('input[name=embed_iframe]');

       if( !isset($iframe[0]->innertext)) {

           return null;

       }

       $iframe = html_entity_decode($iframe[0]->value);

       preg_match("/.*?src=\"(.*?)\".*/", $iframe, $matches);

       $parsed_url = parse_url($matches[1]);
       $code_id = explode('=', $parsed_url['query']);

       $pattern = '<iframe src="'. $matches[1].'" width="640" height="390" scrolling="0" frameborder="0" name="ifr_' . $code_id[1] . '" id="ifr_' . $code_id[1] . '"></iframe>';

       $result = [ 'data' => $data, 'embed' => $pattern ];

       return $result;

    }

    function process_iframe( $data, $tag, $propery, $value, $class = false ) {

       $headers = '';

       $url = html_entity_decode($data['url']);

       $page = curl_request( $url, $headers);

       $dom = new simple_html_dom();
       $dom->load($page);

       $iframe = '';

       if( !$class ) {

           $iframe = $dom->find("{$tag}[{$propery}={$value}] iframe");

       } else {

           $iframe = $dom->find("{$tag}.{$value} iframe");

       }


       $patterns = [ 
                       0 => '/width=["|\'].*?["|\']/',
                       1 => '/height=["|\'].*?["|\']/'
                   ];

       $replacements = [
                           0 => 'width="640"',
                           1 => 'height="390"' 
                       ];

       if( !isset($iframe[0]->outertext) ){

           return null;

       }

       $frame = preg_replace($patterns, $replacements, $iframe[0]->outertext);

       $result = [ 'data' => $data, 'embed' => $frame ];

       return $result;

    }

    function process_object( $data, $tag, $propery, $value, $class = false ) {

       $headers = '';

       $url = html_entity_decode($data['url']);

       $page = curl_request( $url, $headers);

       $dom = new simple_html_dom();
       $dom->load($page);

       $iframe = '';

       if( !$class ) {

           $iframe = $dom->find("{$tag}[{$propery}={$value}] object");

       }else{

           $iframe = $dom->find("{$tag}.{$value} object");

       }

      if( !isset($iframe[0]->outertext) ) {

           return null;

       }

       preg_match("/(<object.*? <\/object>)/", $iframe[0]->outertext, $matches);

       $iframe = $matches[0];


       $patterns = [ 
                       0 => '/width=["|\'].*?["|\']/',
                       1 => '/height=["|\'].*?["|\']/'
                   ];

       $replacements = [
                           0 => 'width="640"',
                           1 => 'height="390"' 
                       ];





       $frame = preg_replace($patterns, $replacements, $iframe);

       $result = [ 'data' => $data, 'embed' => $frame ];

       return $result;

    }

    function process_megogo( $data, $tag, $property, $value ){

       $result = process_iframe($data, $tag, $property, $value);

       preg_match( "/src=\"(.*?)\?.*\"/", $result['embed'], $matches);

       $patterns = [ 
                       0 => '/id="playerFrame"/',
                       1 => '/src=".*?"/'
                   ];

       $replacements = [
                         0 => 'width="640" height="390"',
                         1 => "src=\"http://megogo.net" . $matches[1] . "\" "
                       ];

       $result['embed'] = preg_replace($patterns, $replacements, $result['embed']);

       return $result;

    }

    function process_russia_tv( $data, $tag, $propery, $value, $class = false) {

      $result = process_iframe( $data, 'div', 'id', 'p-pvideo-player', true); //todo: problem maby this


       $patterns = [ 
                       0 => '/ src/',

                   ];

       $replacements = [
                         0 => ' width="640" height="390" src',

                       ];

       $result['embed'] = preg_replace($patterns, $replacements, $result['embed']);

      return $result;



    }