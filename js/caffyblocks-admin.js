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
			var content = $( $.trim(data.html) );
			content.appendTo('.caffyblocks div#' + container_id);
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
});