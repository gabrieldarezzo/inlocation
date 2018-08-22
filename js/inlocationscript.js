(function($) {
	$(document).ready(function() {
		
		$("#icon_id").imagepicker();
		
		
		
		$(".menu-expand").bind( "click", function() {
			
			var $li_this = $(this).parent();
			var idx = $(this).parent().attr('id');
			
			var conf = confirm(inlocation_def.confirm_delete);
			if(!conf){
				return false;
			}
			
			
			var loaderContainer = $( '<span/>', {
				 'class': 'loader-image-container'
				,'id'	: 'ajax-img'
			}).insertAfter( pageTitle );
			var loader = $( '<img/>', {
				src: inlocation_def.path_admin + 'images/loading.gif',
				'class': 'loader-image'
			}).appendTo( loaderContainer );
			
			
			$.ajax({
				 url: ajaxurl
				,type:'POST'
				,dataType: 'json'
				,data: {
					 action: 'inlocation_del_location'
					,idx : idx
				}
				,success: function(json){
					$('#ajax-img').remove();
					$li_this.hide();
					
				},error: function(json){
					$('#ajax-img').remove();
					
				},beforeSend(){
					
				}
			}); //End_ajax
		});
		
		// var sortList  = $('#custom-type-list');
		var animation = $('#loading-animation');
		var pageTitle = $('#lugares');
		
		$('#custom-type-list').sortable({
			update: function(event, ui){
				var loaderContainer = $( '<span/>', {
					 'class': 'loader-image-container'
					,'id'	: 'ajax-img'
				}).insertAfter( pageTitle );
				var loader = $( '<img/>', {
					src: inlocation_def.path_admin + 'images/loading.gif',
					'class': 'loader-image'
				}).appendTo( loaderContainer );
				
				$.ajax({
					 url: ajaxurl
					,type:'POST'
					,dataType: 'json'
					,data: {
						 action: 'inlocation_save_sort_order'
						,order : $('#custom-type-list').sortable('toArray')
					}
			 		,success: function(json){
						$('#ajax-img').remove();
						
						if(!json.success){
							//Msg erro
							pageTitle.after('<div id="msg-ajax" class="error"><p>' + json.data + '</p></div>');
						} else {
							//Mensagem sucesso
							pageTitle.after('<div id="msg-ajax" class="updated"><p>' + json.data +'</p></div>');
						}
					},error: function(json){
						$('#ajax-img').remove();
						pageTitle.after('<div id="msg-ajax" class="error"><p>Ocorreu um erro</p></div>');
					},beforeSend(){
						$('#msg-ajax').remove();
					}
				}); //End_ajax
			}
		});
		
		//begin_get_location
		
		var cep_type = '99999-999'; //Varia de Pais pra pais
		
		$("#cep").mask(cep_type,{completed:function(){
			var ccep = $.trim($("#cep").val());
			if(ccep == ''){
				return false;
			}
			
			var loaderContainer = $( '<span/>', {
				 'class': 'loader-image-container'
				,'id'	: 'ajax-img'
			}).insertAfter( $("#cep") );
			var loader = $( '<img/>', {
				src: inlocation_def.path_admin + 'images/loading.gif',
				'class': 'loader-image'
			}).appendTo( loaderContainer );
		
			
			
			$.ajax({
				 url: ajaxurl
				,type:'POST'
				,dataType: 'json'
				,data: {
					 action	: 'inlocation_endbycep'
					,cep 	: $('#cep').val()
				}
				
				,success: function(json){
					// console.log(json);
					$('#ajax-img').remove();
					
					if(json.logradouro == ''){
						//Msg erro
						pageTitle.after('<div id="msg-ajax" class="error"><p>' + inlocation_def.zip_not_found + '</p></div>');
					} else {
						// $('#cep')val();
						$('#logr_end').val(json.tipo_logradouro + ' ' + json.logradouro);
						$('#logr_bairro').val(json.bairro);
						$('#logr_cidade').val(json.cidade);
						$('#logr_estado').val(json.uf);
						
						$("#logr_nr").focus();
					}
				},error: function(json){
					$('#ajax-img').remove();
					pageTitle.after('<div id="msg-ajax" class="error"><p>' + inlocation_def.error_ajax + '</p></div>');
				},beforeSend(){
					$('#msg-ajax').remove();
				}
			}); //End_ajax
		}});
		
		$("#cep").focus();
		
	});
})(jQuery);