jQuery(document).ready(function($){
	// add logic to create a room in the parent building
	$('.caffyblocks').on('click', 'input.add-room', function(e) {
		e.preventDefault();
		var element      = $(this);
		var room_type    = element.data('type');
		var base_room_id = element.data('base-room-id');
		var building_id = element.data('building-id');
		var room_data = {
			action: 'add_dynamic_room',
			room_type: room_type,
			base_room_id: base_room_id,
			building_id: building_id
		};
		$.post(caffyblocks.ajaxurl, room_data, function(data) {
			prepend_item(element, data);
		});
	});
	// prepend room before the parent
	prepend_item = function(parent, data) {
		if ('undefined' !== typeof(data.success) && 'undefined' !== typeof(data.html) && data.success) {
			var container_id = parent.data('container-id');
			var content = $($.trim(data.html));
			content.appendTo('.caffyblocks div#' + container_id);
			content.find('.psu-box').each( function(i, el) {
				$(el).post_selection_ui();
			} );
		}
	};

	// remove a dynamic room
	$('.caffyblocks').on('click','a.remove-room', function(e) {
		e.preventDefault();
		$(this).parents('div.room-container').remove();
	});

	// move all dynamic rooms to their parent room
	$('.caffyblocks .parent-room').each(function(){
		var container_id = $(this).attr('id');
		$('.caffyblocks div[data-container-id="' + container_id + '"]').appendTo($(this));
	});

	// make dynamic rooms sortable
	$('.caffyblocks .parent-room').sortable();

    // post selection room
	var ajaxCalls = {},
		ajaxCall = function(data,cb) {
			return $.post(
				caffyblocks.ajaxurl,
				$.extend(data, {
					nonce: caffyblocks.nonce
				}),
				cb
			);
		},
		toggleSelection = function(el, force) {
			var $psu_room = $(el).parents('.psu-field'),
				force = force !== undefined ? force : null,
				$toggle = $psu_room.find('.select-psu-posts'),
				$psu_field = $psu_room.find('.psu-field-wrapper')
				psu_index = $psu_room.data('psu_index'),
				$accessory = $psu_room.parents('.caffyblocks-psu-accessory-wrapper');

			if ('psu_index' in ajaxCalls) {
				ajaxCalls.psu_index.abort();
			}

			if (($toggle.hasClass('select-psu-posts-closed') && false !== force) || true === force) {
				ajaxCalls.psu_index = ajaxCall(
					{
						action: 'caffyblocks_add_psu',
						post_types: $psu_room.find('.psu-post_type').val(),
						limit: $accessory.data('limit'),
						index: psu_index,
						field_name: $accessory.data('field_name')
					},
					function( data ) {
						if ('' != data) {
							$toggle.addClass('select-psu-posts-open').removeClass('select-psu-posts-closed').html('Loading...');
							$psu_field.html(data).find('.psu-box').post_selection_ui();
							$toggle.html('Remove Posts');
						}
					}
				);
			}
			else {
				console.log('close');
				$psu_field.html('');

				$toggle.addClass('select-psu-posts-closed').removeClass('select-psu-posts-open').html('Select Posts');
			}
		};

	// add room
	$('body').on('click', '.caffyblocks-add-psu-room', function(e) {
		e.preventDefault();

		var $accessory = $(this).parents('.caffyblocks-psu-accessory-wrapper');

		ajaxCall(
			{
				action: 'caffyblocks_add_psu_room',
				post_types: $accessory.data('post_types'),
				index: $('.psu-field').length,
				field_name: $accessory.data('field_name')
			},
			function( data ) {
				$accessory.find('.caffyblocks-psu-container').append(data);
			}
		);

	});

	// add psu
	$('body').on('click', '.select-psu-posts', function(e) {
		e.preventDefault();
		toggleSelection(this);
	});
	$('body').on('change', '.psu-post_type', function(){
		toggleSelection(this, false);
	});

	// remove psu item
	$('.caffyblocks-psu-accessory-wrapper').on('click', '.psu-remove-item', function(e){
		e.preventDefault();

		var $accessory = $(this).parents('.caffyblocks-psu-accessory-wrapper');
		var baseFieldName = $accessory.data('field_name');

		$(this).parents('.psu-field').remove();

		$('.psu-field').each( function( i, el ) {
			var newFieldName = baseFieldName + '[' + i + '][post_type]';
			$(el).data('psu_index', i);
			$(el).attr('data-psu_index', i);
			$(el).find('select').attr('name', newFieldName);
		} );
	});
});