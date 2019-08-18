function initMap() {
	var latlng = new google.maps.LatLng(lugares[0].lat, lugares[0].lng);

	var map = new google.maps.Map(document.getElementById('map'), {
		 center: latlng
		,zoom: 17
		,mapTypeId: google.maps.MapTypeId.ROADMAP
	});

	 console.log(lugares); // Lugares vem do WP
	for(i in lugares){
		new google.maps.Marker({
			 position: new google.maps.LatLng(lugares[i].lat, lugares[i].lng)
			,map: map
			,title: lugares[i].title
			,draggable: false
			,icon: defs.inwork_img_icon + lugares[i].icon_id
		});
	}
}
