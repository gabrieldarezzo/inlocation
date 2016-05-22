function initMap() {
		
	//Pensar em pegar via API do google (Permitir e talz... ou buscar pelo CEP)
	
	// console.log(defs.inwork_img_icon + 'schools_maps.png');
	
	var latlng = new google.maps.LatLng(lugares[0].lat, lugares[0].lng); //Porem por enquanto pega o primeiro da lista.
	
	var map = new google.maps.Map(document.getElementById('map'), {
		 center: latlng
		,zoom: 17
		,mapTypeId: google.maps.MapTypeId.ROADMAP
	});
	
	// console.log(lugares); // Lugares vem do WP
	for(i in lugares){
		
		if(i == 0){
			//verifica se é o primeiro da lista para inserir 'icon'
			new google.maps.Marker({
				 position: new google.maps.LatLng(lugares[i].lat, lugares[i].lng)
				,map: map
				,title: lugares[i].title
				,draggable: false
				,icon: defs.inwork_img_icon + 'schools_maps.png'
			});
		} else {
			new google.maps.Marker({
				 position: new google.maps.LatLng(lugares[i].lat, lugares[i].lng)
				,map: map
				,title: lugares[i].title
				,draggable: false
			});
		}
	}
}
