<?php

function Streams_stream_response_data()
{
	if (isset(Streams::$cache['removed_count'])) {
		return array('removed_count' => Streams::$cache['removed_count']);
	}
	if (isset(Streams::$cache['result'])) {
		return Streams::$cache['result'];
	}
	if (isset(Streams::$cache['stream'])) {
		$user = Users::loggedInUser();
		$userId = $user ? $user->id : "";
		return Streams::$cache['stream']->exportArray(array('asUserId' => $userId));
	}

	$publisherId = Streams::requestedPublisherId(true);
	$name = Streams::requestedName(true);
	$fields = Streams::requestedFields();

	$user = Users::loggedInUser();
	$userId = $user ? $user->id : 0;

	$streams = array();
	foreach (Streams::fetch($userId, $publisherId, $name, $fields) as $key => $stream) {
		$streams[$key] = $stream->exportArray(array('asUserId' => $userId));
		if ($userId && !empty($fields['join'])) $stream->join();
	}
	return Streams::$cache['result'] = array(
		'stream' => empty($streams) ? null : reset($streams)
	);
}