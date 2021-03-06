<?php

/**
 * This tool generates an inline editor to edit the content or attribute of a stream.
 * @param array $options
 *  An associative array of parameters, containing:
 *  "inplaceType" => Required. The type of the fieldInput. Can be "textarea" or "text"
 *  "stream" => A Streams_Stream object
 *  "staticHtml" => Optional. The static HTML to display when there is nothing to edit
 *  "editing" => If true, then renders the inplace tool in editing mode.
 *  "field" => Optional, name of an field to change instead of the content of the stream
 *  "attribute" => Optional, name of an attribute to change instead of any field.
 *  "editOnClick" => Defaults to true. If true, then edit mode starts only if "Edit" button is clicked.
 *  "selectOnEdit" => Defaults to true. If true, selects all the text when entering edit mode.
 *  "beforeSave" => Optional, reference to a callback to call after a successful save.
 *     This callback can cancel the save by returning false.
 *  "onSave" => Optional, reference to a callback or event to run after a successful save.
 *  "onCancel" => Optional, reference to a callback or event to run after cancel.
 *  "inplace" => Additional fields to pass to the child Q/inplace tool, if any
 */
function Streams_inplace_tool($options)
{
	$stream = $options['stream'];
	if (empty($stream)) {
		throw new Q_Exception_RequiredField(array('field' => 'stream'));
	}
	$toolOptions = array(
		'publisherId' => $stream->publisherId,
		'streamName' => $stream->name,
		'inplaceType' => $options['inplaceType']
	);
	Q::take($options, array('inplace', 'attribute', 'field'), $toolOptions);
	Q_Response::setToolOptions($toolOptions);
	$options['action'] = $stream->actionUrl();
	$options['method'] = 'PUT';
	$options['type'] = $options['inplaceType'];
	if (!empty($options['attribute'])) {
		$field = 'attributes['.urlencode($options['attribute']).']';
	} else {
		$field = !empty($options['field']) ? $options['field'] : 'content';
	}
	switch ($options['inplaceType']) {
		case 'text':
			$options['fieldInput'] = Q_Html::input($field, $stream->content);
			$options['staticHtml'] = Q_Html::text($stream->content);
			break;
		case 'textarea':
			$options['fieldInput'] = Q_Html::textarea($field, 5, 80, $stream->content);
			$options['staticHtml'] = Q_Html::text($stream->content, array("\n"));
			break;
		default:
			return "inplaceType must be 'textarea' or 'text'";
	}
	if (!$stream->testWriteLevel('suggest')) {
		if (!isset($options['classes'])) {
			$options['classes'] = '';
		}
		$staticClass = ($options['inplaceType'] === 'textarea')
			? 'Q_inplace_tool_blockstatic'
			: 'Q_inplace_tool_static';
		return "<span class='Q_inplace_tool_container $options[classes]' style='position: relative;'>"
			. "<div class='$staticClass'>$options[staticHtml]</div></span>";
	}
	return Q::tool("Q/inplace", $options);
}
