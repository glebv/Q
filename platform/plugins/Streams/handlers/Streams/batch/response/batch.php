<?php

function Streams_batch_response_batch()
{
	if (empty($_REQUEST['batch'])) {
		throw new Q_Exception_RequiredField(array('field' => 'batch'));
	}
	
	try {
		$batch = json_decode($_REQUEST['batch'], true);
	} catch (Exception $e) {
		
	}
	if (empty($batch)) {
		throw new Q_Exception_WrongValue(array('field' => 'batch', 'range' => 'valid JSON'));
	}
	
	if (empty($batch['args'])) {
		throw new Q_Exception_RequiredField(array('field' => 'args'));
	}

	// Gather the publisher ids and stream names to fetch
	$to_fetch = array();
	foreach ($batch['args'] as $args) {
		if (count($args) < 4) {
			continue;
		}
		list($action, $slots, $publisherId, $name) = $args;
		if (empty($to_fetch[$publisherId])) {
			$to_fetch[$publisherId] = array();
		}
		$to_fetch[$publisherId][] = $name;
	}
	$user = Users::loggedInUser();
	$userId = $user ? $user->id : "";
	
	// Fetch the actual streams
	$streams = array();
	foreach ($to_fetch as $publisherId => $names) {
		if (empty($streams[$publisherId])) {
			$streams[$publisherId] = array();
		}
		$streams[$publisherId] = array_merge(
			$streams[$publisherId],
			Streams::fetch(
				$userId,
				$publisherId,
				$names,
				'*'
			)
		);
	}
	
	// Now, build the result
	$result = array();
	foreach ($batch['args'] as $args) {
		try {
			$action = $args[0];
			$prev_request = $_REQUEST;
			$extra = !empty($args[4]) ? $args[4] : null;
			if (is_array($extra)) {
				foreach ($extra as $k => $v) {
					$_REQUEST[$k] = $v;
				}
			}
			switch ($action) {
			case 'message':
			case 'participant':
				if (!is_array($extra)) {
					$_REQUEST['ordinal'] = $extra;
				}
				break;
			case 'message':
				if (!is_array($extra)) {
					$_REQUEST['userId'] = $extra;
				}
				break;
			case 'avatar':
				if (!empty($args[2]) and is_array($args[2]) and isset($args[2]['prefix'])) {
					$_REQUEST['prefix'] = $args[2]['prefix'];
					if (!empty($args[2]['limit'])) {
						$_REQUEST['limit'] = $args[2]['limit'];
					}
					if (!empty($args[2]['offset'])) {
						$_REQUEST['offset'] = $args[2]['offset'];
					}
				} else {
					$_REQUEST['userIds'] = $args[2];
				}
			}
			Q_Request::$slotNames_override = is_array($args[1]) ? $args[1] : explode(',', $args[1]);
			Q_Request::$method_override = 'GET';
			if (count($args) >= 4) {
				Streams::$requestedPublisherId_override = $publisherId = $args[2];
				Streams::$requestedName_override = $name = $args[3];
				if (empty($streams[$publisherId][$name])) {
					throw new Q_Exception_MissingRow(array(
						'table' => 'Stream', 
						'criteria' => "'publisherId' => $publisherId, 'name' => $name"
					));
				}
				Streams::$cache['stream'] = $streams[$publisherId][$name];
			}
			Q::event(
				"Streams/$action/response", 
				compact('streams', 'publisherId', 'name', 'extra', 'user', 'userId')
			);
			$slots = array_diff(Q_Response::slots(true), array('batch'));
			$result[] = compact('slots');
		} catch (Exception $e) {
			$result[] = array('errors' => Q_Exception::toArray(array($e)));
		}
		$prev_request = $_REQUEST;
		Q_Request::$slotNames_override = null;
		Q_Request::$method_override = null;
		Streams::$requestedPublisherId_override = null;
		Streams::$requestedName_override = null;
	}
	
	return $result;
}