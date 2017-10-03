/* ==========================================================================
  jQuery use for plugin
============================================================================= */
(function($) {
   var DevSolution = {
   		init: function(){
   			// Post Status
   			DevSolution.PostStatus();

            // On Click Expiration Date
            DevSolution.expirationdate_ajax_add_meta();
            DevSolution.expirationdate_toggle_category();
   		},

   		PostStatus: function(){
   			var status = $('.dev-private-post');
   			
   			status.live('change',function(){
   				var $this 	= $(this),
   					status  = $(this).val(),
   					post_ID = $this.data('post_id');

   					var data = {
						action: 'dev_oferta_ajax',
			            post_id: post_ID,
			            status: status
					};
					
				 	$.post(dev_ajax.ajaxurl, data, function(res) {
						if(res == 'ok'){
							alert("Me Sukses eshte ndryshuar statusi i postit");
						}else{
							alert("Diqka eshte gabim!!");
						}
				 	});
				 	return false;

   			});
   		},

         expirationdate_ajax_add_meta: function(){

            var parent_div = $("#dev_post_expired"),
                expire     = parent_div.find('#enable-expirationdate'); 

            expire.live('click', function(event){
               var $this = $(this);
               
               if($this.is(':checked')){
                  var enable = 'true';

                  if (document.getElementById('expirationdate_month')) {
                     document.getElementById('expirationdate_month').disabled = false;
                     document.getElementById('expirationdate_day').disabled = false;
                     document.getElementById('expirationdate_year').disabled = false;
                     document.getElementById('expirationdate_hour').disabled = false;
                     document.getElementById('expirationdate_minute').disabled = false;
                  }
                  document.getElementById('expirationdate_expiretype').disabled = false;
                  var cats = document.getElementsByName('expirationdate_category[]');
                  var max = cats.length;
                  for (var i=0; i<max; i++) {
                     cats[i].disabled = '';
                  }
               }else{
                  if (document.getElementById('expirationdate_month')) {
                     document.getElementById('expirationdate_month').disabled = true;
                     document.getElementById('expirationdate_day').disabled = true;
                     document.getElementById('expirationdate_year').disabled = true;
                     document.getElementById('expirationdate_hour').disabled = true;
                     document.getElementById('expirationdate_minute').disabled = true;
                  }
                  document.getElementById('expirationdate_expiretype').disabled = true;
                  var cats = document.getElementsByName('expirationdate_category[]');
                  var max = cats.length;
                  for (var i=0; i<max; i++) {
                     cats[i].disabled = 'disable';
                  }
                  var enable = 'false';
               }

               return true;

            });
         },

         expirationdate_toggle_category: function(){

            var parent_div = $("#dev_post_expired"),
                id         = parent_div.find('#expirationdate_expiretype');

                id.live('change', function(){

                 
                  var $this = $(this);
                  if ($this.val() == 'category') {
                        $('#expired-category-selection').show();
                  } else if ($this.val() == 'category-add') {
                        $('#expired-category-selection').show(); //TEMP  
                  } else if ($this.val() == 'category-remove') {
                        $('#expired-category-selection').show(); //TEMP
                  } else {
                        $('#expired-category-selection').hide();
                  }
                });
               
         },

         expirationdate_toggle_defaultdate: function(id){
            if (id.options[id.selectedIndex].value == 'custom') {
               $('#expired-custom-container').show();
            } else {
               $('#expired-custom-container').hide();
            }
         }


   }

   DevSolution.init();
})(jQuery);
