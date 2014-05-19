jQuery(document).ready(function() {
		
	// When the user clicks on the button
	jQuery("#btn.ready").click(function() {

		var nb_images = 0;
		var total_images = 0;

		// First, we test if we have at least a TypePad URL
		var typepad_url = jQuery("input[name='typepad_url']").val();

		if(typepad_url != "") {
			// Get the original URL
			var original_url = jQuery("input[name='domain_name']").val();
			if(original_url == "") {
				original_url = typepad_url;
			}

			// Add a trailing slash to URLs if needed
			if (original_url.substr(-1) != '/') original_url += '/';
			if (typepad_url.substr(-1) != '/') typepad_url += '/';

			// Add the assets folder to the URLs
			original_url_assets = original_url + '.a/';
			typepad_url_assets = typepad_url + '.a/';

			// Adapt the URLs to be able to use them in the regex
			original_url = original_url_assets.replace(/\//g, "\\\/");
			original_url = original_url.replace(/\./g, "\\\.");
			original_url += "[0-9a-z]*-[0-9a-z]*";
			typepad_url = typepad_url_assets.replace(/\//g, "\\\/");
			typepad_url = typepad_url.replace(/\./g, "\\\.");
			typepad_url += "[0-9a-z]*-[0-9a-z]*";

			// Deactivate the button
			jQuery(this).removeClass("ready").addClass("processing").empty().html("We are re-importing your assets...<br />This may take many minutes, depending on the volume of assets to import.");

			// Display the title of the follow-up
			var nb_posts_edited = 0;
			jQuery("#follow-up h3#nb_posts").html("<span>"+nb_posts_edited+"</span> posts have been parsed.");

			// Get all the posts
			var data = {
				action: "get_posts"
			};

			jQuery.post(ajaxurl, data, function(response) {
				
				// We decode the response and store the resuls in the posts variable.
				var obj = jQuery.parseJSON(response);

				// For each of the posts, we browse the content and the excerpt
				for(var i=0; i<obj.length; i++) {

					// Get the content and the excerpt
					var content = obj[i].post_content;
					var excerpt = obj[i].post_excerpt;
					
					var content_urls = [];

					// Get the urls that are in the content
					var original_url_regex = original_url;
					var original_regex = new RegExp(original_url_regex, "g");
					var typepad_url_regex = typepad_url;
					var typepad_regex = new RegExp(typepad_url_regex, "g");

					var content_assets = content.match(original_regex);
					var excerpt_assets = content.match(original_regex);

					if(content_assets != null) {
						for(var j=0; j<content_assets.length; j++) {
							content_urls.push(content_assets[j]);
						}
					}
					if(excerpt_assets != null) {
						for(var j=0; j<excerpt_assets.length; j++) {
							content_urls.push(excerpt_assets[j]);
						}
					}

					var content_assets = content.match(typepad_regex);
					var excerpt_assets = content.match(typepad_regex);

					if(content_assets != null) {
						for(var j=0; j<content_assets.length; j++) {
							content_urls.push(content_assets[j]);
						}
					}
					if(excerpt_assets != null) {
						for(var j=0; j<excerpt_assets.length; j++) {
							content_urls.push(excerpt_assets[j]);
						}
					}

					// Increment the number of posts parsed
					nb_posts_edited++;

					// Prepend the title to the list of posts
					var title = obj[i].post_title+"<br />";
					jQuery("#follow-up #posts-list").append(title);

					// Display the number of posts parsed
					jQuery("#follow-up h3#nb_posts span").empty().text(nb_posts_edited);

					total_images += content_urls.length;
					jQuery("#follow-up h3#nb_images span#outof").empty().text(total_images);


					// console.log(content_urls);

					// Get the images and upload them on the server
					if((content_urls.length > 0) && (content_urls != null)) {
						for(var j=0; j<content_urls.length; j++) {
							var data_content = {
								action: "copy_images",
								image_url: content_urls[j],
								content: content,
								excerpt: excerpt,
								id: obj[i].ID,
								original_url: original_url_assets,
								typepad_url: typepad_url_assets
							}

							jQuery.post(ajaxurl, data_content, function(response_content) {
								var obj_content = jQuery.parseJSON(response_content);
								console.log(obj_content);
								if(obj_content.response == true) {
									nb_images++;
									jQuery("#follow-up h3#nb_images span#imported").empty().text(nb_images);
								}
							});
						}
					}
				}

				// Change the text of the button
				jQuery(this).empty().text("your content has been re-imported successfully!");

			});
		}

	});

});



function cleanArray(array) {
	var i, j, len = array.length, out = [], obj = {};
	
	for (i = 0; i < len; i++) {
		obj[array[i]] = 0;
	}
	
	for (j in obj) {
		out.push(j);
	}
	
	return out;
}