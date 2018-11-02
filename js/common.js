var myDomo={
	
	init:function(){
		console.log('Inicio!');
		myDomo.printHourBar();
		myDomo.windowMain();
		
		jQuery('div.sensorUno').click(function(){myDomo.windowAjustTemp();});
		jQuery('div.sensorDos').click(function(){myDomo.windowPredicionAemet();});
		jQuery('button.btnToMain').click(function(){myDomo.windowMain();});
		jQuery('button.btnRefreshApp').click(function(){window.location.reload();});
		jQuery('button.btnDay').click(function(){myDomo.changeDay();});
		jQuery('button.btnUp').click(function(){myDomo.btnUpClick(this);});
		jQuery('button.btnDown').click(function(){myDomo.btnDownClick(this);});
		
		myDomo.refresh();
		myDomo.refreshExternalTemp();
		
		window.setInterval(function(){ myDomo.fecha_hora() }, 500);
	},
	
	printHourBar:function(){
		var totalWidth=jQuery('div.hourAjust').width();
		jQuery('div.hourAjust .lineHour').width((totalWidth/24)-3);
	},
	
	fecha_hora:function(){
		var today = new Date();
		var hr = today.getHours();
		var min = today.getMinutes();
		var sec = today.getSeconds();
		
		checkTime=function(i) {
			//Add a zero in front of numbers<10
			if (i < 10) { i = "0" + i;}
			return i;
		}
		hr = checkTime(hr);
		min = checkTime(min);
		sec = checkTime(sec);
		
		jQuery('div.date span.hour').text( hr + ":" + min + ":" + sec);
		
		var months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
		var days = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
		var curWeekDay = days[today.getDay()];
		var curDay = today.getDate();
		var curMonth = months[today.getMonth()];
		var curYear = today.getFullYear();
		//var date = curWeekDay+", "+curDay+" de "+curMonth+" de "+curYear;
		var date = curWeekDay+", "+curDay+" de "+curMonth;

		jQuery('div.date span.date').text(date);
		

	},
	
	refresh:function(){
		jQuery.ajax({
			dataType:'json',
			url:'/cgi-bin/temp.cgi',
			success:function(data){
				roundTemp=Math.round(data.temp*100,2)/100;
				decimalTemp=Math.round((roundTemp % 1)*100);
				
				roundHumedad=Math.round(data.humidity*100,2)/100;
				
				jQuery('div.sensorUno span.temp1').text(Math.floor(data.temp));
				jQuery('div.sensorUno span.temp2').text('.'+decimalTemp);
				
				jQuery('div.humedad1').html('<img src="./imgs/tint_icon.png"/> '+roundHumedad+'%');
				
				window.setTimeout(myDomo.refresh,10000);
			},
			error:function(){
				window.setTimeout(myDomo.refresh,10000);
			}
		});
	},
	
	refreshExternalTemp:function(){
		jQuery.ajax({
			dataType:'json',
			url:'/index.php?module=api_temp',
			success:function(data){
				
				jQuery('div.sensorDos span.temp1').text(data.entero);
				jQuery('div.sensorDos span.temp2').text('.'+data.decimal);
				jQuery('div.humedad2').html('<img src="./imgs/tint_icon.png"/> '+data.humedad+'%');
				window.setTimeout(myDomo.refreshExternalTemp,300000);
			},
			error:function(){
				window.setTimeout(myDomo.refreshExternalTemp,300000);
			}
		});	
	},
	
	windowAjustTemp:function(){
		jQuery('.content').hide();
		jQuery('.content.windowAjustTemp').fadeIn();
	},
	
	windowPredicionAemet:function(){
		jQuery('.content').hide();
		jQuery('.content.windowPredicionAemet').fadeIn();
	},
	
	windowMain:function(){
		jQuery('.content').hide();
		jQuery('.content.windowMain').fadeIn();
	},
	
	changeDay:function(){
		
	},
	
	btnUpClick:function(obj){
		$input=jQuery(obj).parent().find('input[type=number]');
		$input.val(parseInt($input.val())+1);
	},
	
	btnDownClick:function(obj){
		$input=jQuery(obj).parent().find('input[type=number]');
		$input.val(parseInt($input.val())-1);
	}
	
};

jQuery(document).ready(function(){
	myDomo.init();
}); 
