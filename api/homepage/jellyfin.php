<?php

trait JellyfinHomepageItem
{
	public function testConnectionJellyfin()
	{
		if (empty($this->config['jellyfinURL'])) {
			$this->setAPIResponse('error', 'Jellyfin URL is not defined', 422);
			return false;
		}
		if (empty($this->config['jellyfinToken'])) {
			$this->setAPIResponse('error', 'Jellyfin Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$url = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$json = json_decode($response->body);
				if (is_array($json) || is_object($json)) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				} else {
					$this->setAPIResponse('error', 'URL or token incorrect', 409);
					return false;
				}
			} else {
				$this->setAPIResponse('error', 'Jellyfin Connection Error', 500);
				return true;
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function resolveJellyfinItem($itemDetails)
	{
		$item = isset($itemDetails['NowPlayingItem']['Id']) ? $itemDetails['NowPlayingItem'] : $itemDetails;
		// Static Height & Width
		$height = $this->getCacheImageSize('h');
		$width = $this->getCacheImageSize('w');
		$nowPlayingHeight = $this->getCacheImageSize('nph');
		$nowPlayingWidth = $this->getCacheImageSize('npw');
		$actorHeight = 450;
		$actorWidth = 300;
		// Cache Directories
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheDirectoryWeb = 'plugins/images/cache/';
		// Types
		switch (@$item['Type']) {
			case 'Series':
				$jellyfinItem['type'] = 'tv';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['BackdropImageTags'][0]) ? 'Backdrop' : '');
				break;
			case 'Episode':
				$jellyfinItem['type'] = 'tv';
				$jellyfinItem['title'] = $item['SeriesName'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']);
				$jellyfinItem['key'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']) . "-list";
				$jellyfinItem['nowPlayingThumb'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] : false);
				$jellyfinItem['nowPlayingKey'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] . '-np' : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] . '-np' : false);
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['ParentBackdropImageTags'][0]) ? 'Backdrop' : '');
				$jellyfinItem['nowPlayingTitle'] = @$item['SeriesName'] . ' - ' . @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = 'S' . @$item['ParentIndexNumber'] . ' · E' . @$item['IndexNumber'];
				break;
			case 'MusicAlbum':
			case 'Audio':
				$jellyfinItem['type'] = 'music';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = (isset($item['AlbumId']) ? $item['AlbumId'] : @$item['ParentBackdropItemId']);
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = isset($item['AlbumId']) ? $item['AlbumId'] : $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = (isset($item['ParentBackdropItemId']) ? "Primary" : "Backdrop");
				$jellyfinItem['nowPlayingTitle'] = @$item['AlbumArtist'] . ' - ' . @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['Album'];
				break;
			case 'Movie':
				$jellyfinItem['type'] = 'movie';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$jellyfinItem['nowPlayingTitle'] = @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			case 'Video':
				$jellyfinItem['type'] = 'video';
				$jellyfinItem['title'] = $item['Name'];
				$jellyfinItem['secondaryTitle'] = '';
				$jellyfinItem['summary'] = '';
				$jellyfinItem['ratingKey'] = $item['Id'];
				$jellyfinItem['thumb'] = $item['Id'];
				$jellyfinItem['key'] = $item['Id'] . "-list";
				$jellyfinItem['nowPlayingThumb'] = $item['Id'];
				$jellyfinItem['nowPlayingKey'] = $item['Id'] . "-np";
				$jellyfinItem['metadataKey'] = $item['Id'];
				$jellyfinItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
				$jellyfinItem['nowPlayingTitle'] = @$item['Name'];
				$jellyfinItem['nowPlayingBottom'] = @$item['ProductionYear'];
				break;
			default:
				return false;
		}
		$jellyfinItem['uid'] = $item['Id'];
		$jellyfinItem['imageType'] = (isset($item['ImageTags']['Primary']) ? "Primary" : false);
		$jellyfinItem['elapsed'] = isset($itemDetails['PlayState']['PositionTicks']) && $itemDetails['PlayState']['PositionTicks'] !== '0' ? (int)$itemDetails['PlayState']['PositionTicks'] : null;
		$jellyfinItem['duration'] = isset($itemDetails['NowPlayingItem']['RunTimeTicks']) ? (int)$itemDetails['NowPlayingItem']['RunTimeTicks'] : (int)(isset($item['RunTimeTicks']) ? $item['RunTimeTicks'] : '');
		$jellyfinItem['watched'] = ($jellyfinItem['elapsed'] && $jellyfinItem['duration'] ? floor(($jellyfinItem['elapsed'] / $jellyfinItem['duration']) * 100) : 0);
		$jellyfinItem['transcoded'] = isset($itemDetails['TranscodingInfo']['CompletionPercentage']) ? floor((int)$itemDetails['TranscodingInfo']['CompletionPercentage']) : 100;
		$jellyfinItem['stream'] = @$itemDetails['PlayState']['PlayMethod'];
		$jellyfinItem['id'] = $item['ServerId'];
		$jellyfinItem['session'] = @$itemDetails['DeviceId'];
		$jellyfinItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
		$jellyfinItem['bandwidthType'] = 'wan';
		$jellyfinItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
		$jellyfinItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
		$jellyfinItem['user'] = ($this->config['homepageShowStreamNames'] && $this->qualifyRequest($this->config['homepageShowStreamNamesAuth'])) ? @(string)$itemDetails['UserName'] : "";
		$jellyfinItem['userThumb'] = '';
		$jellyfinItem['userAddress'] = (isset($itemDetails['RemoteEndPoint']) ? $itemDetails['RemoteEndPoint'] : "x.x.x.x");
		$jellyfinURL = $this->config['jellyfinURL'] . '/web/index.html#!/itemdetails.html?id=';
		$jellyfinItem['address'] = $this->config['jellyfinTabURL'] ? rtrim($this->config['jellyfinTabURL'], '/') . "/web/#!/item/item.html?id=" . $jellyfinItem['uid'] : $jellyfinURL . $jellyfinItem['uid'] . "&serverId=" . $jellyfinItem['id'];
		$jellyfinItem['nowPlayingOriginalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['nowPlayingImageType'] . '&img=' . $jellyfinItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $jellyfinItem['nowPlayingKey'] . '$' . $this->randString();
		$jellyfinItem['originalImage'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['imageType'] . '&img=' . $jellyfinItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $jellyfinItem['key'] . '$' . $this->randString();
		$jellyfinItem['openTab'] = $this->config['jellyfinTabURL'] && $this->config['jellyfinTabName'] ? true : false;
		$jellyfinItem['tabName'] = $this->config['jellyfinTabName'] ? $this->config['jellyfinTabName'] : '';
		// Stream info
		$jellyfinItem['userStream'] = array(
			'platform' => @(string)$itemDetails['Client'],
			'product' => @(string)$itemDetails['Client'],
			'device' => @(string)$itemDetails['DeviceName'],
			'stream' => @$itemDetails['PlayState']['PlayMethod'],
			'videoResolution' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]['Width']) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Width'] : '',
			'throttled' => false,
			'sourceVideoCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : '',
			'videoCodec' => @$itemDetails['TranscodingInfo']['VideoCodec'],
			'audioCodec' => @$itemDetails['TranscodingInfo']['AudioCodec'],
			'sourceAudioCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][1]) ? $itemDetails['NowPlayingItem']['MediaStreams'][1]['Codec'] : (isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : ''),
			'videoDecision' => $this->streamType(@$itemDetails['PlayState']['PlayMethod']),
			'audioDecision' => $this->streamType(@$itemDetails['PlayState']['PlayMethod']),
			'container' => isset($itemDetails['NowPlayingItem']['Container']) ? $itemDetails['NowPlayingItem']['Container'] : '',
			'audioChannels' => @$itemDetails['TranscodingInfo']['AudioChannels']
		);
		// Genre catch all
		if (isset($item['Genres'])) {
			$genres = array();
			foreach ($item['Genres'] as $genre) {
				$genres[] = $genre;
			}
		}
		// Actor catch all
		if (isset($item['People'])) {
			$actors = array();
			foreach ($item['People'] as $key => $value) {
				if (@$value['PrimaryImageTag'] && @$value['Role']) {
					if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
						$actorImage = $cacheDirectoryWeb . (string)$value['Id'] . '-cast.jpg';
					}
					if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg') && (time() - 604800) > filemtime($cacheDirectory . (string)$value['Id'] . '-cast.jpg') || !file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
						$actorImage = 'api/v2/homepage/image?source=jellyfin&type=Primary&img=' . (string)$value['Id'] . '&height=' . $actorHeight . '&width=' . $actorWidth . '&key=' . (string)$value['Id'] . '-cast';
					}
					$actors[] = array(
						'name' => (string)$value['Name'],
						'role' => (string)$value['Role'],
						'thumb' => $actorImage
					);
				}
			}
		}
		// Metadata information
		$jellyfinItem['metadata'] = array(
			'guid' => $item['Id'],
			'summary' => @(string)$item['Overview'],
			'rating' => @(string)$item['CommunityRating'],
			'duration' => @(string)$item['RunTimeTicks'],
			'originallyAvailableAt' => @(string)$item['PremiereDate'],
			'year' => (string)isset($item['ProductionYear']) ? $item['ProductionYear'] : '',
			//'studio' => (string)$item['studio'],
			'tagline' => @(string)$item['Taglines'][0],
			'genres' => (isset($item['Genres'])) ? $genres : '',
			'actors' => (isset($item['People'])) ? $actors : ''
		);
		if (file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg')) {
			$jellyfinItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $jellyfinItem['nowPlayingKey'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg')) {
			$jellyfinItem['imageURL'] = $cacheDirectoryWeb . $jellyfinItem['key'] . '.jpg';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $jellyfinItem['nowPlayingKey'] . '.jpg')) {
			$jellyfinItem['nowPlayingImageURL'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['nowPlayingImageType'] . '&img=' . $jellyfinItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $jellyfinItem['nowPlayingKey'] . '';
		}
		if (file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $jellyfinItem['key'] . '.jpg') || !file_exists($cacheDirectory . $jellyfinItem['key'] . '.jpg')) {
			$jellyfinItem['imageURL'] = 'api/v2/homepage/image?source=jellyfin&type=' . $jellyfinItem['imageType'] . '&img=' . $jellyfinItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $jellyfinItem['key'] . '';
		}
		if (!$jellyfinItem['nowPlayingThumb']) {
			$jellyfinItem['nowPlayingOriginalImage'] = $jellyfinItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
			$jellyfinItem['nowPlayingKey'] = "no-np";
		}
		if (!$jellyfinItem['thumb']) {
			$jellyfinItem['originalImage'] = $jellyfinItem['imageURL'] = "plugins/images/cache/no-list.png";
			$jellyfinItem['key'] = "no-list";
		}
		if (isset($useImage)) {
			$jellyfinItem['useImage'] = $useImage;
		}
		return $jellyfinItem;
	}
	
	public function getJellyfinHomepageStreams()
	{
		if (!$this->config['homepageJellyfinEnabled']) {
			$this->setAPIResponse('error', 'Jellyfin homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageJellyfinStreams']) {
			$this->setAPIResponse('error', 'Jellyfin homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJellyfinAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJellyStreamsAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['jellyfinURL'])) {
			$this->setAPIResponse('error', 'Jellyfin URL is not defined', 422);
			return false;
		}
		if (empty($this->config['jellyfinToken'])) {
			$this->setAPIResponse('error', 'Jellyfin Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$url = $url . '/Sessions?api_key=' . $this->config['jellyfinToken'] . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		try {
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getJellyfinHomepageRecent()
	{
		if (!$this->config['homepageJellyfinEnabled']) {
			$this->setAPIResponse('error', 'Jellyfin homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageJellyfinRecent']) {
			$this->setAPIResponse('error', 'Jellyfin homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJellyfinAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJellyfinRecentAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['jellyfinURL'])) {
			$this->setAPIResponse('error', 'Jellyfin URL is not defined', 422);
			return false;
		}
		if (empty($this->config['jellyfinToken'])) {
			$this->setAPIResponse('error', 'Jellyfin Token is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/Latest?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['jellyfinToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = $this->resolveJellyfinItem($child);
					}
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getJellyfinHomepageMetadata($array)
	{
		if (!$this->config['homepageJellyfinEnabled']) {
			$this->setAPIResponse('error', 'Jellyfin homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJellyfinAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['jellyfinURL'])) {
			$this->setAPIResponse('error', 'Jellyfin URL is not defined', 422);
			return false;
		}
		if (empty($this->config['jellyfinToken'])) {
			$this->setAPIResponse('error', 'Jellyfin Token is not defined', 422);
			return false;
		}
		$key = $array['key'] ?? null;
		if (!$key) {
			$this->setAPIResponse('error', 'Jellyfin Metadata key is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jellyfinURL']);
		$options = ($this->localURL($url)) ? array('verify' => false) : array();
		$username = false;
		$showPlayed = false;
		$userId = 0;
		try {
			if (isset($this->user['username'])) {
				$username = strtolower($this->user['username']);
			}
			// Get A User
			$userIds = $url . "/Users?api_key=" . $this->config['jellyfinToken'];
			$response = Requests::get($userIds, array(), $options);
			if ($response->success) {
				$jellyfin = json_decode($response->body, true);
				foreach ($jellyfin as $value) { // Scan for admin user
					if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
						$userId = $value['Id'];
					}
					if ($username && strtolower($value['Name']) == $username) {
						$userId = $value['Id'];
						$showPlayed = false;
						break;
					}
				}
				$url = $url . '/Users/' . $userId . '/Items/' . $key . '?EnableImages=true&Limit=' . $this->config['homepageRecentLimit'] . '&api_key=' . $this->config['jellyfinToken'] . ($showPlayed ? '' : '&IsPlayed=false') . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$jellyfin = json_decode($response->body, true);
				if (isset($jellyfin['NowPlayingItem']) || isset($jellyfin['Name'])) {
					$items[] = $this->resolveJellyfinItem($jellyfin);
				}
				$api['content'] = array_filter($items);
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			} else {
				$this->setAPIResponse('error', 'Jellyfin Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jellyfin Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
}