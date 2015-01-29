var bindFunctions = function()
{
	// the following block of code deals with drag and drop of images for MD5 hashing
	var search_dropdown = jQuery('#search_form_image');

	if (isEventSupported('dragstart') && isEventSupported('drop') && !!window.FileReader)
	{
		search_dropdown.on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			e.originalEvent.dataTransfer.dropEffect = 'copy';
		});

		search_dropdown.on('dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
		});

		search_dropdown.on('drop', function(event) {
			if (event.originalEvent.dataTransfer){
				if (event.originalEvent.dataTransfer.files.length) {
					event.preventDefault();
					event.stopPropagation();

					findSameImageFromFile(event.originalEvent.dataTransfer);
				}
			}
		});
	}

	var is_posting = false;

	var clickCallbacks = {

		checkAll: function(el, post, event)
		{
			var checkboxes = el.parent().parent().find('input[type=checkbox]');
			checkboxes.each(function(id, element) {
				jQuery(element).attr('checked', 'checked');
			});
			el.parent().find('.uncheck').show();
			el.hide();
		},

		uncheckAll: function(el, post, event)
		{
			var checkboxes = el.parent().parent().find('input[type=checkbox]');
			checkboxes.each(function(id, element) {
				jQuery(element).attr('checked', false);
			});
			el.parent().find('.check').show();
			el.hide();
		},

		hideThread: function(el, post, event)
		{
			var hiddenBoardThreads = JSON.parse(localStorage.getItem("hiddenBoardThreads/" + el.data("board"))) || {};
			var num = el.data("doc-id");

			hiddenBoardThreads[num] = true;
			localStorage.setItem("hiddenBoardThreads/" + el.data("board"), JSON.stringify(hiddenBoardThreads));

			jQuery(".doc_id_" + num).hide();
			jQuery(".stub_doc_id_" + num).show();
		},

		showThread: function(el, post, event)
		{
			var hiddenBoardThreads = JSON.parse(localStorage.getItem("hiddenBoardThreads/" + el.data("board"))) || {};
			var num = el.data("doc-id");

			delete hiddenBoardThreads[num];
			localStorage.setItem("hiddenBoardThreads/" + el.data("board"), JSON.stringify(hiddenBoardThreads));

			jQuery(".doc_id_" + num).show();
			jQuery(".stub_doc_id_" + num).hide();
		},

		hidePost: function(el, post, event)
		{
			var hiddenBoardPosts = JSON.parse(localStorage.getItem("hiddenBoardPosts/" + el.data("board"))) || {};
			var num = el.data("doc-id");

			hiddenBoardPosts[num] = true;
			localStorage.setItem("hiddenBoardPosts/" + el.data("board"), JSON.stringify(hiddenBoardPosts));

			jQuery(".doc_id_" + num).hide();
			jQuery(".stub_doc_id_" + num).show();
		},

		showPost: function(el, post, event)
		{
			var hiddenBoardPosts = JSON.parse(localStorage.getItem("hiddenBoardPosts/" + el.data("board"))) || {};
			var num = el.data("doc-id");

			delete hiddenBoardPosts[num];
			localStorage.setItem("hiddenBoardPosts/" + el.data("board"), JSON.stringify(hiddenBoardPosts));

			jQuery(".doc_id_" + num).show();
			jQuery(".stub_doc_id_" + num).hide();
		},

		highlight: function(el, post, event)
		{
			if (post)
			{
				toggleHighlight(post);
			}
		},

		quote: function(el, post, event)
		{
			jQuery("#reply_chennodiscursus").insertAtCaret(">>" + post + "\n");
		},

		comment: function(el, post, event)
		{
			var file_el = jQuery("#file_image");
			var progress_pos = 0;
			var progress_el = jQuery("#reply .progress .bar");

			// if there's an image and the browser doesn't support FormData, use a normal upload process
			if (file_el.val() && window.FormData === undefined)
			{
				return true;
			}

			if (is_posting)
			{
				return false;
			}

			is_posting = true;

			var originalText = el.attr('value');
			var el_parent = el.parent();
			el.attr({'value': backend_vars.gettext['submit_state']});
			el_parent.find('[name=reply_gattai]').attr({disabled:'disabled'})
			el_parent.find('[name=reply_gattai_spoilered]').attr({disabled:'disabled'})
			//el.parent().find('[name=reply_gattai], [name=reply_gattai_spoilered]')

			// to make sure nobody gets pissed off with a blocked button
			var buttonTimeout = setTimeout(function(){
				el.attr({'value': originalText});
				el.removeAttr('disabled');
			}, 10000);

			var reply_alert = jQuery('#reply_ajax_notices');
			reply_alert.removeClass('error').removeClass('success');

			var data_obj = {
				reply_numero: jQuery("#reply_numero").val(),
				reply_bokunonome: jQuery("#reply_bokunonome").val(),
				reply_elitterae: jQuery("#reply_elitterae").val(),
				reply_talkingde: jQuery("#reply_talkingde").val(),
				reply_chennodiscursus: jQuery("#reply_chennodiscursus").val(),
				reply_nymphassword: jQuery("#reply_nymphassword").val(),
				reply_postas: jQuery("#reply_postas").val() === undefined ? 'N' : jQuery("#reply_postas").val(),
				recaptcha_challenge_field: jQuery("#recaptcha_challenge_field").val(),
				recaptcha_response_field: jQuery("#recaptcha_response_field").val(),
				reply_gattai: 'Submit',
				reply_last_limit: typeof backend_vars.last_limit === "undefined" ? null : backend_vars.last_limit,
				latest_doc_id: backend_vars.latest_doc_id,
				theme: backend_vars.selected_theme
			};

			// sets the type of submit (reply_gattai, reply_gattai_spoilered)
			data_obj[el.attr('name')] = true;

			// support for checkbox spoiler
			if (el_parent.find('[name=reply_spoiler]:checked').length)
			{
				data_obj.reply_spoiler = true;
			}

			data_obj[backend_vars.csrf_token_key] = getCookie(backend_vars.csrf_token_key);

			progress_el.parent().animate({'opacity': '1.0'}, 300);

			var ajax_object = {
				url: backend_vars.site_url + backend_vars.board_shortname + '/submit/' ,
				dataType: 'json',
				type: 'POST',
				data: data_obj,
				cache: false,
				xhr: function() {
					var xhr = jQuery.ajaxSettings.xhr();
					 if (xhr instanceof window.XMLHttpRequest) {
						xhr.upload.addEventListener('progress', function(evt){
							var progress_local = Math.ceil(evt.loaded / evt.total * 100);
							if (evt.lengthComputable && progress_pos !== progress_local)
							{
								progress_pos = progress_local;
								progress_el.css('width', (progress_pos) + '%');
							}
						}, false);
					}
					return xhr;
				},
				success: function(data, textStatus, jqXHR) {
					if (typeof window.Recaptcha !== "undefined")
					{
						window.Recaptcha.reload();
					}

					jQuery("#recaptcha_response_field").val('');
					if (typeof data.captcha !== "undefined")
					{
						jQuery('.recaptcha_widget').show();
						jQuery('.rules_box').hide()
						return false;
					}

					if (typeof data.error !== "undefined")
					{
						reply_alert.html(data.error);
						reply_alert.addClass('error'); // deals with showing the alert
						return false;
					}

					jQuery('.rules_box').show();
					jQuery('.recaptcha_widget').hide();

					reply_alert.html(data.success);
					reply_alert.addClass('success'); // deals with showing the alert
					jQuery("#reply_chennodiscursus").val("");
					jQuery("#reply_nymphassword").val(getCookie('reply_password'));
					file_el.replaceWith('<input type="file" name="file_image" id="file_image" size="16">');


					// redirect in case of new threads
					if (data_obj.reply_numero < 1)
					{
						window.location = backend_vars.site_url + backend_vars.board_shortname + '/thread/'
							+ data.thread_num + '/';
						return false;
					}

					insertPost(data, textStatus, jqXHR);
				},
				error: function(jqXHR, textStatus, errorThrown) {
					reply_alert.html('Connection error.');
					reply_alert.addClass('error');
					reply_alert.show();
				},
				complete: function() {
					// clear button's timeout, we can deal with the rest now
					is_posting = false;
					clearTimeout(buttonTimeout);
					el.attr({'value': originalText});
					el_parent.find('[name=reply_gattai]').removeAttr('disabled');
					el_parent.find('[name=reply_gattai_spoilered]').removeAttr('disabled');
					progress_el.css('width', '0%');
					progress_el.parent().animate({'opacity': '0.0'}, 300);
				}
			}

			// if we have FormData support, we can upload files!
			if (window.FormData !== undefined)
			{
				ajax_object.processData = false;
				ajax_object.contentType = false;
				var data_formdata = new FormData();
				jQuery.each(data_obj, function(id, val){
					data_formdata.append(id, val);
				})

				if (typeof file_el[0] !== 'undefined' && typeof file_el[0].files !== 'undefined')
				{
					data_formdata.append('file_image', file_el[0].files[0])
				}

				ajax_object.data = data_formdata;
			}

			jqxhr = jQuery.ajax(ajax_object);

			event.preventDefault();
		},

		realtimeThread: function(el, post, event)
		{
			realtimethread();
			event.preventDefault();
		},

		expandThread: function(el, post, event)
		{
			var thread_num = el.data('thread-num');

			if (! el.data('expanded'))
			{
				el.spin('small');
				jQuery.ajax({
					url: backend_vars.api_url + '_/api/chan/thread/',
					dataType: 'json',
					type: 'GET',
					data: {
						num : thread_num,
						board: backend_vars.board_shortname,
						theme: backend_vars.selected_theme
					},
					success: function(data, textStatus, jqXHR){
						insertPost(data, textStatus, jqXHR);
						var post_count = 0;
						var media_count = 0;
						jQuery.each(data[thread_num].posts, function(id, val){
							post_count++;
							if (val.media !== null)
							{
								media_count++;
							}
						})
						var thread = jQuery('article.thread[data-thread-num=' + thread_num + '] ');
						var displayed_string = post_count + ' posts ' +
							(media_count > 0 ? 'and ' + media_count + ' ' + (media_count == 1 ? 'image' : 'images') : '') + ' displayed';
						thread.find('.omitted_text').text(displayed_string);
						el.data('expanded', true).html('<i class="icon icon-resize-small"></i>');
						el.spin(false);
					}
				});
			}
			else
			{
				var thread = jQuery('article.thread[data-thread-num=' + thread_num + ']');
				var articles =  thread.find('aside.posts article');
				articles.slice(0, articles.length - 5).hide();
				var post_count = articles.filter(':hidden').length;
				var media_count = articles.find('.thread_image_box:hidden').length;
				var omitted_string = post_count + ' posts ' +
					(media_count > 0 ? 'and ' + media_count + ' ' + (media_count == 1 ? 'image' : 'images') : '') + ' omitted';
				thread.find('.omitted_text').text(omitted_string);
				el.data('expanded', false).html('<i class="icon icon-resize-full"></i>');
			}

			return false;
		},

		clearSearch: function(el, post, event)
		{
			var form = jQuery('.advanced_search').find('form')
			form.find(':input').not(':input[type=submit]').not(':input[type=reset]').val('');

			// keep the first radio set
			var done_names = [];
			form.find('[type=radio]').each(function (idx) {
				if (!jQuery.inArray(jQuery(this).attr('name'), done_names))
				{
					jQuery(this).attr('checked', true);
					done_names.push(jQuery(this).attr('name'));
				}
			});
		},

		mod: function(el, post, event)
		{
			el.attr({'disabled': 'disabled'});
			_data = {
				board: el.data('board'),
				id: el.data('id'),
				action: el.data('action'),
				theme: backend_vars.selected_theme
			};
			_data[backend_vars.csrf_token_key] = getCookie(backend_vars.csrf_token_key);
			jQuery.ajax({
				url: backend_vars.api_url + '_/api/chan/mod_actions/',
				dataType: 'json',
				type: 'POST',
				cache: false,
				data: _data,
				success: function(data){
					el.removeAttr('disabled');
					if (typeof data.error !== "undefined")
					{
						alert(data.error);
						return false;
					}

					// might need to be upgraded to array support
					switch(el.data('action'))
					{
						case 'remove_post':
							jQuery('.doc_id_' + el.data('id')).remove();
							break;
						case 'delete_image':
							jQuery('.doc_id_' + el.data('doc-id')).find('.thread_image_box:eq(0) img')
								.attr('src', backend_vars.images['missing_image'])
								.css({
									width: backend_vars.images['missing_image_width'],
									height: backend_vars.images['missing_image_height']
								});
							break;
						case 'delete_report':
							el.closest('.report_reason').remove();
							break;
						case 'ban_user':
							jQuery('.doc_id_' + el.data('id')).find('[data-action=ban_user]').text('Banned');
							break;
						case 'ban_image_global':
						case 'ban_image_local':
							jQuery('.doc_id_' + el.data('doc-id')).find('.thread_image_box:eq(0) img')
								.attr('src', backend_vars.images['banned_image'])
								.css({
									width: backend_vars.images['banned_image_width'],
									height: backend_vars.images['banned_image_height']
								});
							break;
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {

				},
				complete: function() {
				}
			});
			return false;
		},

		activateModeration: function(el, post, event)
		{
			jQuery('.post_mod_controls button[data-function]').attr({'disabled': 'disabled'});
			setTimeout(function(){
				jQuery('.post_mod_controls button[data-function]').removeAttr('disabled');
			}, 700);
			jQuery('.post_mod_controls').show();
			jQuery('button[data-function=activateModeration]').parent().hide();
		},

		closeModal: function(el, post)
		{
			el.closest(".modal").modal('hide');
			return false;
		},

		'delete': function(el, post, event)
		{
			var modal = jQuery("#post_tools_modal");
			var foolfuuka_reply_password = getCookie('foolfuuka_reply_password');
			modal.find(".title").html('Delete &raquo; Post No. ' + el.data("post-id"));
			modal.find(".modal-error").html('');
			modal.find(".modal-loading").hide();
			modal.find(".modal-information").html('\
			<span class="modal-label">Password</span>\n\
			<input type="hidden" class="modal-post-id" value="' + el.data("post") + '" />\n\
			<input type="hidden" class="modal-board" value="' + el.data("board") + '" />\n\
			<input type="password" class="modal-password" />');
			modal.find(".submitModal").data("action", 'delete');
			modal.find(".modal-password").val(backend_vars.user_pass);
		},


		report: function(el, post, event)
		{
			var modal = jQuery("#post_tools_modal");
			modal.find(".title").html('Report &raquo; Post No.' + el.data("post-id"));
			modal.find(".modal-error").html('');
			modal.find(".modal-loading").hide();
			modal.find(".modal-information").html('\
			<input type="hidden" class="modal-post-id" value="' + el.data("post") + '" />\n\
			<input type="hidden" class="modal-board" value="' + el.data("board") + '" />\n\
			<span class="modal-field">Comment</span>\n\
			<textarea class="modal-comment"></textarea>\n\
			<span class="model-note">Note: Requests for content removal and take-downs must be sent via email.</span>');
			modal.find(".submitModal").data("action", 'report');
		},

		reportMedia: function(el, post, event)
		{
			var modal = jQuery("#post_tools_modal");
			modal.find(".title").html('Report &raquo; Media No.' + el.data("media-id"));
			modal.find(".modal-error").html('');
			modal.find(".modal-loading").hide();
			modal.find(".modal-information").html('\
			<span class="modal-label">Media ID</span>\n\
			<input type="text" disabled="disabled" class="modal-media-id" value="' + el.data("media-id") + '" />\n\
			<br>\n\
			<input type="hidden" class="modal-board" value="' + el.data("board") + '" />\n\
			<span class="modal-field">Comment</span>\n\
			<textarea class="modal-comment"></textarea>');
			modal.find(".submitModal").data("action", 'report_media');
		},

		ban: function(el, post, event)
		{
			var modal = jQuery("#post_tools_modal");
			modal.find(".title").html('Ban user with IP ' + el.data("ip"));
			modal.find(".modal-error").html('');
			modal.find(".modal-loading").hide();
			modal.find(".modal-information").html('\
			<span class="modal-label">IP</span>\n\
			<input type="text" class="modal-ip" value="' + el.data("ip") + '" /><br/>\n\
			<span class="modal-label">Days</span>\n\
			<input type="text" class="modal-days" value="3" /><br/>\n\
			<span class="modal-label modal-board-ban" style="text-align:left">Only this board</span>\n\
			<input type="radio" name="board" checked value="board" /><br/>\n\
			<span class="modal-label modal-global-ban">Global</span>\n\
			<input type="radio" name="board" value="global" /><br/>\n\
			<span class="modal-field">Comment</span>\n\
			<input type="hidden" class="modal-board" value="' + el.data("board") + '" />\n\
			<textarea class="modal-comment"></textarea>');
			modal.find(".submitModal").data("action", 'ban');
		},

		submitModal: function(el, post, event)
		{
			var modal = jQuery("#post_tools_modal");
			var loading = modal.find(".modal-loading");
			var action = el.data("action");
			var _board = modal.find(".modal-board").val();
			var _doc_id = modal.find(".modal-post-id").val();
			var _href = backend_vars.api_url+'_/api/chan/user_actions/';
			var _data = {};

			if (action == 'report') {
				_data = {
					action: 'report',
					board: _board,
					doc_id: _doc_id,
					reason: modal.find(".modal-comment").val(),
					csrf_fool: backend_vars.csrf_hash
				};
			}
			else if (action == 'report_media') {
				_data = {
					action: 'report_media',
					board: _board,
					media_id: modal.find(".modal-media-id").val(),
					reason: modal.find(".modal-comment").val(),
					csrf_fool: backend_vars.csrf_hash
				};
			}
			else if (action == 'delete') {
				_data = {
					action: 'delete',
					board: _board,
					doc_id: _doc_id,
					password: modal.find(".modal-password").val(),
					csrf_fool: backend_vars.csrf_hash
				};
			}
			else if (action == 'ban')
			{
				_href = backend_vars.api_url+'_/api/chan/mod_actions/';
				_data = {
					action: 'ban_user',
					board: modal.find('.modal-board').val(),
					board_ban: modal.find('input:radio[name=board]:checked').val(),
					length: modal.find('.modal-days').val() * 24 * 60 * 60,
					ip: modal.find('.modal-ip').val(),
					reason: modal.find('.modal-comment').val()
				};
			}
			else {
				// Stop It! Unable to determine which action to use.
				return false;
			}

			_data[backend_vars.csrf_token_key] = getCookie(backend_vars.csrf_token_key);

			jQuery.post(_href, _data, function(result) {
				loading.hide();
				if (typeof result.error !== 'undefined') {
					modal.find(".modal-error").html('<div class="alert alert-error" data-alert="alert"><a class="close" href="#">&times;</a><p>' + result.error + '</p></div>').show();
					return false;
				}
				modal.modal('hide');

				if (action == 'delete') {
					jQuery('.doc_id_' + _doc_id).hide();
				}
				if (action == 'report') {
					jQuery('.doc_id_' + _doc_id).find('.text:eq(0)').after('<div class="report_reason">' + result.success + '</div>');
				}
			}, 'json');
			return false;
		},

		clearLatestSearches: function(el, post, event)
		{
			setCookie('search_latest_5', '', 0, '/', backend_vars.cookie_domain);
			jQuery('li.latest_search').each(function(idx){
				jQuery(this).remove();
			});
		},

		searchUser: function(el, post, event)
		{
			window.location.href = backend_vars.site_url + el.data('board') +
				'/search/poster_ip/' + el.data('poster-ip');
		},

		searchUserGlobal: function(el, post, event)
		{
			window.location.href = backend_vars.site_url + '_/search/poster_ip/' + el.data('poster-ip');
		}
	}


	// unite all the onclick functions in here
	jQuery(document.body).on("click", "a[data-function], button[data-function], input[data-function]", function(event) {
		var el = jQuery(this);
		var post = el.data("post");
		return clickCallbacks[el.data("function")](el, post, event);
	});

	jQuery(document.body).on("click", ".search_box, .search-query", function(event) {
		event.stopPropagation();
	});

	jQuery(document.body).click(function(event) {
		var search_input = jQuery('#search_form_comment');
		jQuery('.search-query').val(search_input.val())
		jQuery('.search_box').hide();
	});

	jQuery('.search-query').focus(function() {
		var el = jQuery(this);
		var offset = el.offset();
		var width = el.outerWidth();
		var search_box = jQuery('.search_box');
		var comment_wrap = search_box.find('.comment_wrap');
		var comment_wrap_pos = comment_wrap.position();
		search_box.css({top: (offset.top - 11) + 'px', right: (jQuery(window).width() - (offset.left + width) - 16) + 'px' }).show();
		el.parents('.open').removeClass('open');
		search_box.find('input[name=text]').focus();
		return false;
	});

	// how could we make it work well on cellphones?
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent))
	{
		return false;
	}


	// variable for ajax backlinks that we can clear them if the mouse hovered out
	var backlink_jqxhr;
	var timeout;
	var posts_being_fetched = []; // posts that are being xhr'd right now
	var show_post_board;
	var show_post_num; // the number of the post that has to show in the xhr callback, when 0 don't run popups

	// hover functions go here
	jQuery("#main").on("mouseover mouseout", "article a[data-backlink]", function(event)
	{

		if (event.type == "mouseover")
		{

			var backlink = jQuery("#backlink");
			var el = jQuery(this);

			var pos = el.offset();
			var height = el.height();
			var width = el.width();

			if (el.attr('data-backlink') != 'true')
			{
				// gallery
				var thread_id = el.attr('data-backlink');
				quote = backend_vars.threads_data[thread_id];
				backlink.css('display', 'block');
				backlink.html(quote.formatted);
			}
			else if (jQuery('#' + el.data('post')).hasClass('post'))
			{
				// normal posts
				var toClone = jQuery('#' + el.data('post'));
				if (toClone.length == 0)
					return false;
				backlink.css('display', 'block');
				backlink.html(toClone.clone().show());
			}
			else if (typeof backend_vars.loaded_posts[el.data('board') + ':' + el.data('post')] !== 'undefined')
			{
				// if we know the post doesn't exist
				if (backend_vars.loaded_posts[el.data('board') + ':' + el.data('post')] === false)
				{
					shakeBacklink(el);
					return false;
				}
				var data = backend_vars.loaded_posts[el.data('board') + ':' + el.data('post')];
				backlink.html(data.formatted);
				backlink.css('display', 'block');
			}
			else
			{
				show_post_board = el.data('board');
				show_post_num = el.data('post');

				// avoid multiple connections
				if (posts_being_fetched[el.data('board') + ':' + el.data('post')]) {
					return false;
				}

				timeout = setTimeout(function() {
					var backlink_spin = el;
					backlink_spin.spin('small');
					backlink_jqxhr = jQuery.ajax({
						url: backend_vars.api_url + '_/api/chan/post/' ,
						dataType: 'json',
						type: 'GET',
						cache: false,
						data: {
							board: el.data('board'),
							num: el.data('post'),
							theme: backend_vars.selected_theme
						},
						success: function(data){
							backlink_spin.spin(false);
							if (typeof data.error !== "undefined")
							{
								backend_vars.loaded_posts[el.data('board') + ':' + el.data('post')] = false;
								shakeBacklink(el);
								return false;
							}
							backend_vars.loaded_posts[el.data('board') + ':' + el.data('post')] = data;

							// avoid showing the post if the mouse is not there anymore
							if (show_post_board === el.data('board') && show_post_num === el.data('post'))
							{
								backlink.html(data.formatted);
								backlink.find("time").localize('ddd dd mmm yyyy HH:MM:ss');
								backlink.css('display', 'block');
								showBacklink(backlink, pos, height, width);
							}
						},
						error: function() {
							shakeBacklink(el);
						},
						complete: function() {
							posts_being_fetched[el.data('board') + ':' + el.data('post')] = false;
						}
					});
					return false;
				}, 50);
			}

			backlink.find("time").localize('ddd dd mmm yyyy HH:MM:ss');
			showBacklink(backlink, pos, height, width);
		}
		else
		{
			show_post_board = false;
			show_post_num = false;
			clearTimeout(timeout);
			jQuery("#backlink").css('display', 'none').html('');
		}
	});
}

var hideThreads = function()
{
	if (typeof backend_vars.board_shortname !== "undefined" && typeof backend_vars.thread_id === "undefined")
	{
		var hiddenBoardThreads = JSON.parse(localStorage.getItem("hiddenBoardThreads/" + backend_vars.board_shortname)) || {};

		for (var num in hiddenBoardThreads)
		{
			jQuery(".doc_id_" + num).hide();
			jQuery(".stub_doc_id_" + num).show();
		}
	}
}

var hidePosts = function()
{
	if (typeof backend_vars.board_shortname !== "undefined")
	{
		var hiddenBoardPosts = JSON.parse(localStorage.getItem("hiddenBoardPosts/" + backend_vars.board_shortname)) || {};

		for (var num in hiddenBoardPosts)
		{
			jQuery(".doc_id_" + num).hide();
			jQuery(".stub_doc_id_" + num).show();
		}
	}
}

var shakeBacklink = function(el)
{
	el.css({position:'relative'});
	el.animate({left: '-5px'},100)
		.animate({left: '+5px'}, 100)
		.animate({left: '-5px'}, 100)
		.animate({left: '+5px'}, 100)
		.animate({left: '+0px'}, 100, 'linear', function(){
			el.css({position:'static'});
		});

}

var showBacklink = function(backlink, pos, height, width)
{
	if (jQuery(window).width()/2 < pos.left + width/2)
	{
		backlink.css({
			right: (jQuery(window).width() - pos.left - width) + 'px',
			top: (pos.top + height + 3) + 'px',
			left: 'auto'
		});
	}
	else
	{
		backlink.css({
			left: (pos.left) + 'px',
			top: (pos.top + height + 3) + 'px',
			right: 'auto'
		});
	}

	backlink.find('.stub').remove();

	// for lazyload
	var swap_image = backlink.find('[data-original]');
	if (swap_image.length > 0)
	{
		swap_image.attr('src', swap_image.attr('data-original'));
	}
}

var backlinkify = function(elem, post_id, subnum)
{
	var backlinks = {};
	if (subnum > 0)
	{
		post_id += "_" + subnum;
	}

	elem.find("a[data-backlink=true]").each(function(idx, post) {
		if (jQuery(post).text().indexOf('/') >= 0)
		{
			return true;
		}

		var p_id = jQuery(post).attr('data-post');
		var board_shortname = jQuery(post).attr('data-board');

		if (typeof backlinks[p_id] === "undefined")
		{
			backlinks[p_id] = [];
		}

		if (typeof backend_vars.last_limit === "undefined")
		{
			if (typeof backend_vars.thread_id === "undefined")
			{
				backlinks[p_id].push('<a href="' + backend_vars.site_url + board_shortname + '/post/' + post_id + '" data-function="highlight" data-backlink="true" data-post="' + post_id + '">&gt;&gt;' + post_id.replace('_', ',') + '</a>');

				// convert /post/ links to real urls
				if (jQuery('#' + p_id).length)
				{
					jQuery(post).attr('href', backend_vars.site_url + board_shortname + '/post/' + p_id);
				}
			}
			else
			{
				backlinks[p_id].push('<a href="' + backend_vars.site_url + board_shortname + '/thread/' + backend_vars.thread_id + '/#' + post_id + '" data-function="highlight" data-backlink="true" data-post="' + post_id + '">&gt;&gt;' + post_id.replace('_', ',') + '</a>');

				// convert /post/ links to real urls
				if (jQuery('#' + p_id).length)
				{
					if (backend_vars.thread_id == p_id)
					{
						jQuery(post).addClass('op');
					}

					jQuery(post).attr('href', backend_vars.site_url + board_shortname + '/thread/' + backend_vars.thread_id + '/#' + p_id);
				}
			}
		}
		else
		{
			backlinks[p_id].push('<a href="' + backend_vars.site_url + board_shortname + '/last/' + backend_vars.last_limit + '/' + backend_vars.thread_id + '/#' + post_id + '" data-function="highlight" data-backlink="true" data-post="' + post_id + '">&gt;&gt;' + post_id.replace('_', ',') + '</a>');

			// convert /post/ links to real urls
			if (jQuery('#' + p_id).length)
			{
				jQuery(post).attr('href', backend_vars.site_url + board_shortname + '/last/' + backend_vars.last_limit + '/' + backend_vars.thread_id + '/#' + p_id);
			}
		}

		backlinks[p_id] = eliminateDuplicates(backlinks[p_id]);
	});

	jQuery.each(backlinks, function(key, val){
		var post = jQuery("#" + key);
		if (post.length == 0)
			return false;

		var post_backlink = post.find(".post_backlink:eq(0)");
		var already_backlinked = post_backlink.text().replace('>>', '').split(' ');
		jQuery.each(already_backlinked, function(i,v){
			if (typeof val[v] !== "undefined")
			{
				delete val[v];
			}
		});

		if (post_backlink.find('[data-post=' + post_id  + ']').length == 0)
		{
			post_backlink.html(post_backlink.html() + ((post_backlink.html().length > 0)?" ":"") + val.join(" "));
			post_backlink.parent().show();
		}
	});
}

var timelapse = 10;
var currentlapse = 0;
var realtimethread = function(){
	clearTimeout(currentlapse);
	jQuery.ajax({
		url: backend_vars.api_url + '_/api/chan/thread/',
		dataType: 'json',
		type: 'GET',
		data: {
			num : backend_vars.thread_id,
			board: backend_vars.board_shortname,
			latest_doc_id: backend_vars.latest_doc_id,
			theme: backend_vars.selected_theme,
			last_limit: typeof backend_vars.last_limit === "undefined" ? null : backend_vars.last_limit
		},
		success: insertPost,
		error: function(jqXHR, textStatus, errorThrown) {
			//timelapse = 10;
		},
		complete: function() {
			currentlapse = setTimeout(realtimethread, timelapse*1000);
		}
	});

	return false;
}

var ghost = false;
var insertPost = function(data, textStatus, jqXHR)
{
	var w_height = jQuery(document).height();
	var found_posts = false;

	if (data !== null)
	{
		jQuery.each(data, function(id, val)
		{
			if (typeof val.posts !== "undefined")
			{
				var aside = jQuery('article.thread[data-thread-num=' + id + '] aside.posts');
				jQuery.each(val.posts, function(idx, value)
				{
					found_posts = true;
					var post = jQuery(value.formatted);

					post.find("time").localize('ddd dd mmm yyyy HH:MM:ss');
					post.find('[rel=tooltip]').tooltip({
						placement: 'top',
						delay: 200
					});

					post.find('[rel=tooltip_right]').tooltip({
						placement: 'right',
						delay: 200
					});

					// avoid inserting twice
					if (jQuery('.doc_id_' + value.doc_id).length != 0)
					{
						jQuery('.doc_id_' + value.doc_id).remove();
					}

					aside.append(post);
					backlinkify(post, value.num, value.subnum);

					$('pre,code').each(function(i, block) {
						hljs.highlightBlock(block);
					});

					if(backend_vars.latest_doc_id < value.doc_id)
					{
						backend_vars.latest_doc_id = value.doc_id;
					}

					// update comment box when encountering ghost posts
					if (ghost === false && value.subnum > 0)
					{
						jQuery("#file_image").parent().remove();
						jQuery("#reply_chennodiscursus").attr("placeholder", backend_vars.gettext['ghost_mode']);

						ghost = true;
					}
				});
			}
		});
	}

	if (found_posts)
	{
		if (jQuery('#reply :focus').length > 0)
		{
			window.scrollBy(0, jQuery(document).height() - w_height);
		}

		enableRealtimeThread();

		timelapse = 10;
	}
	else
	{
		if (timelapse < 120)
		{
			timelapse += 5;
		}
	}
}

var findSameImageFromFile = function(obj)
{
	var reader = new FileReader();
	reader.onloadend = function(evt){
		if (evt.target.readyState == FileReader.DONE) {
			var fileContents = evt.target.result;
			var digestBytes = Crypto.MD5(Crypto.charenc.Binary.stringToBytes(fileContents), {
				asBytes: true
			});
			var digestBase64 = Crypto.util.bytesToBase64(digestBytes);
			var digestBase64URL = digestBase64.replace('==', '').replace(/\//g, '_').replace(/\+/g, '-');
			jQuery('#search_form_image').val(digestBase64URL);
		}
	}

	reader.readAsBinaryString(obj.files[0]);
}

var toggleHighlight = function(id)
{
	var classn = 'highlight'
	jQuery("article").each(function() {
		var post = jQuery(this);

		if (post.hasClass(classn))
		{
			post.removeClass(classn);
		}

		if (post.attr("id") == id)
		{
			post.addClass(classn);
		}
	})
}

var realtime = false;
var enableRealtimeThread = function()
{
	if (realtime == false)
	{
		realtime = true;

		jQuery('.js_hook_realtimethread').html(backend_vars.gettext['thread_is_real_time'] + ' <a class="btnr" href="#" data-function="realtimeThread">' + backend_vars.gettext['update_now'] + '</a>');
		setTimeout(realtimethread, 10000);
	}
}

jQuery(document).ready(function() {

	// settings
	jQuery.support.cors = true;
	backend_vars.loaded_posts = [];

	var lazyloaded = jQuery('img.lazyload');
	if(lazyloaded.length > 149)
	{
		lazyloaded.lazyload({
			threshold: 1000,
			event: 'scroll'
		});
	}

	// check if input[date] is supported, so we can use by default input[text] with placeholder without breaking w3
	var i = document.createElement("input");
	i.setAttribute("type", "date");
	if (i.type !== "text")
	{
		jQuery('#date_end').replaceWith(jQuery('<input>').attr({id: 'date_end', name: 'end', type: 'date'}));
		jQuery('#date_start').replaceWith(jQuery('<input>').attr({id: 'date_start', name: 'start', type: 'date'}));
	}

	// opera doesn't play well with the modal transition
	if (navigator.appName == "Opera" && navigator.userAgent.match(/Version\/12\./))
	{
		$('#post_tools_modal').removeClass('fade');
	}

	// firefox sucks at styling input, so we need to add size="", that guess what? It's not w3 compliant!
	jQuery('#file_image').attr({size: '16'});

	var post = location.href.split(/#/);
	if (post[1]) {
		if (post[1].match(/^q\d+(_\d+)?$/)) {
			post[1] = post[1].replace('q', '').replace('_', ',');
			jQuery("#reply_chennodiscursus").append(">>" + post[1] + "\n");
			post[1] = post[1].replace(',', '_');

		}

		toggleHighlight(post[1]);
	}

	if (typeof backend_vars.thread_id !== "undefined" && (Math.round(new Date().getTime() / 1000) - backend_vars.latest_timestamp < 6 * 60 * 60))
	{
		enableRealtimeThread();
	}

	bindFunctions();
	hideThreads();
	hidePosts();

	// localize and add 4chan tooltip where title
	jQuery("article time").localize('ddd dd mmm yyyy HH:MM:ss').filter('[title]').tooltip({
		placement: 'top',
		delay: 300,
		animation: false
	});

	jQuery('input[title]').tooltip({
		placement: 'right',
		delay: 200,
		animation: false
	});

	jQuery('li.latest_search').tooltip({
		placement: 'left',
		animation: false
	});

	jQuery('#thread_o_matic .thread_image_box').tooltip({
		placement: 'bottom',
		animation: true
	});
});

$.fn.extend({
	insertAtCaret: function(text){
		var obj;

		if (typeof this[0].name !== 'undefined')
		{
			obj = this[0];
		}
		else
		{
			obj = this;
		}

		var insPos = obj.selectionStart, endPos = obj.selectionEnd;

		obj.value = obj.value.substring(0, insPos) + text + obj.value.substring(endPos, obj.value.length);
		obj.selectionStart = insPos + text.length;
		obj.selectionEnd = insPos + text.length;
	}
});
