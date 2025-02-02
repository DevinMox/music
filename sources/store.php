<?php 
runPlugin("OnStorePage");
if (empty($path['options'][1])) {
	header("Location: $site_url/404");
	exit();
}

$type = $music->pageType = secure($path['options'][1]);

if (!in_array($type, ['songs', 'album', 'top', 'products'])) {
	header("Location: $site_url/404");
	exit();
}
$records = 0;
$page = 'content';
$html_list = "<div class='no-songs-found text-center'>" . lang("No songs found on this store.") . "</div>";

if ($type == 'products') {
    $page = 'products';
	$products = $db->where('active', 1)->orderBy('id', 'DESC')->get(T_PRODUCTS, 10);
	if (!empty($products)) {
        $records = count($products);
		$html_list = '';
		foreach ($products as $key => $value) {
			$music->product = GetProduct($value->id);
			if (!empty($music->product)) {
                $html_list .= loadPage('store/product_list',array('id' => $music->product->id,
                                                                  'url' => $music->product->url,
                                                                  'data_load' => $music->product->data_load,
                                                                  'image' => $music->product->images[0]['image'],
                                                                  'title' => $music->product->title,
                                                                  'rating' => $music->product->rating,
                                                                  'f_price' => $music->product->formatted_price));
			}
		}
	}
}
if ($type == 'songs') {
    $getSongs = $db->where('price', 0, '>')->where('album_id', 0)->orderBy('id', 'DESC')->get(T_SONGS, 10);
    if (!empty($getSongs)) {
        $records = count($getSongs);
        $html_list = '';
        foreach ($getSongs as $key => $song) {
            $songData = songData($song, false, false);
            if (!empty($songData)) {
                $music->songData = $songData;
                $html_list .= loadPage('store/song-list', [
                    't_thumbnail' => $songData->thumbnail,
                    't_id' => $songData->id,
                    't_title' => $songData->title,
                    't_artist' => $songData->publisher->name,
                    't_uartist' => $songData->publisher->username,
                    't_url' => $songData->url,
                    't_artist_url' => $songData->publisher->url,
                    't_price' => $songData->price,
                    't_audio_id' => $songData->audio_id,
                    't_duration' => $songData->duration,
                    't_posted' => $songData->time_formatted,
                    't_key' => ($key + 1)
                ]);
            }
        }
    }
}

if ($type == 'album') {
    $page = 'album';
    $getAlbums = $db->where('price', 0, '>')->orderBy('id', 'DESC')->get(T_ALBUMS, 10);
    if (!empty($getAlbums)) {
        $records = count($getAlbums);
        $html_list = '';
        foreach ($getAlbums as $key => $album) {
            if (!empty($album)) {
                $publisher = userData($album->user_id);
                $html_list .= loadPage('store/albums', [
                    'id' => $album->id,
                    'album_id' => $album->album_id,
                    'user_id' => $album->user_id,
                    'artist' => $publisher->username,
                    'title' => $album->title,
                    'description' => $album->description,
                    'category_id' => $album->category_id,
                    'thumbnail' => getMedia($album->thumbnail),
                    'time' => $album->time,
                    'registered' => $album->registered,
                    'price' => $album->price,
                    'artist_name' => $publisher->name,
                    'songs' => number_format_mm($db->where('album_id', $album->id)->getValue(T_SONGS, 'count(*)'))
                ]);
            }
        }
    }
}

$top_songs = '';
$top_album = '';
$top_product = '';
if ($type == 'top') {
    $page = 'top';

    $getTopAlbums = $db->rawQuery('SELECT DISTINCT 
                                      `'.T_ALBUMS.'`.`id`
                                    FROM
                                      `'.T_PURCHAES.'`
                                      INNER JOIN `'.T_SONGS.'` ON (`'.T_PURCHAES.'`.`track_id` = `'.T_SONGS.'`.`id`)
                                      INNER JOIN `'.T_ALBUMS.'` ON (`'.T_SONGS.'`.`album_id` = `'.T_ALBUMS.'`.`id`)
                                    ORDER BY
                                      `'.T_PURCHAES.'`.`time` DESC LIMIT 14');

    if (!empty($getTopAlbums)) {
        foreach ($getTopAlbums as $key => $album) {
            if (!empty($album)) {
                $albumData = albumData($album->id, true, true, false);
                $top_album .= loadPage('store/albums', [
                    'id' => $albumData->id,
                    'album_id' => $albumData->album_id,
                    'user_id' => $albumData->user_id,
                    'artist' => $albumData->publisher->name,
                    'title' => $albumData->title,
                    'description' => $albumData->description,
                    'category_id' => $albumData->category_id,
                    'thumbnail' => $albumData->thumbnail,
                    'time' => $albumData->time,
                    'registered' => $albumData->registered,
                    'price' => $albumData->price
                ] );

            }
        }
    }

    $getTopSongs = $db->rawQuery('SELECT track_id, COUNT(track_id) AS count FROM `'.T_PURCHAES.'` GROUP BY track_id ORDER BY count,`time` DESC LIMIT 10');
    if (!empty($getTopSongs)) {
        foreach ($getTopSongs as $key => $song) {
            $songData = songData($song->track_id);
            if (!empty($songData)) {
                $music->songData = $songData;
                $top_songs .= loadPage('store/song-list', [
                    't_thumbnail' => $songData->thumbnail,
                    't_id' => $songData->id,
                    't_title' => $songData->title,
                    't_artist' => $songData->publisher->name,
                    't_uartist' => $songData->publisher->username,
                    't_url' => $songData->url,
                    't_artist_url' => $songData->publisher->url,
                    't_price' => $songData->price,
                    't_audio_id' => $songData->audio_id,
                    't_duration' => $songData->duration,
                    't_posted' => $songData->time_formatted,
                    't_key' => ($key + 1)
                ]);
            }
        }
    }

    $getTopProducts = $db->rawQuery('SELECT product_id, COUNT(product_id) AS count FROM `'.T_ORDERS.'` GROUP BY product_id ORDER BY count,`time` DESC LIMIT 10');
    if (!empty($getTopProducts)) {
        foreach ($getTopProducts as $key => $order) {
            $music->product = GetProduct($order->product_id);
            if (!empty($music->product)) {
                $top_product .= loadPage('store/top_product', [
                    't_thumbnail' => $music->product->images[0]['image'],
                    't_id' => $music->product->id,
                    't_title' => $music->product->title,
                    't_artist' => $music->product->user_data->name,
                    't_uartist' => $music->product->user_data->username,
                    't_url' => $music->product->url,
                    'data_load' => $music->product->data_load,
                    't_artist_url' => $music->product->user_data->url,
                    't_price' => $music->product->price,
                    't_posted' => time_Elapsed_String($music->product->time),
                    't_key' => ($key + 1)
                ]);
            }
        }
    }

}

$music->site_title = lang("Store");
$music->site_description = $music->config->description;
$music->site_pagename = "store";
$music->site_content = loadPage("store/".$page, [
    'records' => $records,
	'html_content' => $html_list,
    'top_songs' => $top_songs,
	'top_album' => $top_album,
    'top_product' => $top_product,
	'filters' => loadPage('store/filters')
]);