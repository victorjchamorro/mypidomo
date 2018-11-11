var myDomo={
	
	dataTemperature:null,
	curWeekDay:null,
	
	init:function(){
		console.log('Inicio!');
		myDomo.printHourBar();
		myDomo.windowMain();
		
		//jQuery('div.sensorUno').click(function(){myDomo.windowAjustTemp();});
		//jQuery('div.sensorDos').click(function(){myDomo.windowPredicionAemet();});
		jQuery('button.btnToMain').click(function(){myDomo.windowMain();});
		jQuery('button.btnRefreshApp').click(function(){window.location.reload();});
		jQuery('button.btnHaltApp').click(function(){myDomo.halt();});
		jQuery('button.btnDay').click(function(){myDomo.changeDay();});
		jQuery('button.btnUp').click(function(){myDomo.btnUpClick(this);});
		jQuery('button.btnDown').click(function(){myDomo.btnDownClick(this);});
		jQuery('span.lineHour').click(function(){myDomo.lineHourClick(this);});
		jQuery('.statusBar .menu').click(function(){myDomo.showMenu();});
		jQuery('.mainMenu button.menuTemperatura').click(function(){myDomo.windowAjustTemp();myDomo.hideMenu();});
		jQuery('.mainMenu button.menuPrediccion').click(function(){myDomo.windowPredicionAemet();myDomo.hideMenu();});
		jQuery('.mainMenu button.menuHistorial').click(function(){myDomo.windowHistorial();myDomo.hideMenu();});
		
		myDomo.refresh();
		myDomo.refreshExternalTemp();
		
		window.setInterval(function(){ myDomo.fecha_hora() }, 1000);
	},
	
	halt:function(){
		if (confirm('¿Apagar MyPiDomo?')){
			window.location.href='/index.php?module=halt';
		}
	},
	
	printHourBar:function(){
		var totalWidth=jQuery('div.hourAjust').width();
		jQuery('div.hourAjust .lineHour').width((totalWidth/24)-3);
	},
	
	showMenu:function(){
		jQuery('.mainMenu').show(300,function(){
			jQuery('.content').click(function(){myDomo.hideMenu();});
		});
	},
	
	hideMenu:function(){
		jQuery('.mainMenu').hide();
		jQuery('.content').unbind('click');
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
			//url:'/cgi-bin/temp.cgi',
			url:'/?module=getLastData',
			success:function(data){
				roundTemp=Math.round(data.temp*10)/10;
				decimalTemp=Math.round((roundTemp % 1)*10);
				
				roundHumedad=Math.round(data.humidity*100)/100;
				
				jQuery('div.sensorUno span.temp1').text(Math.floor(data.temp));
				jQuery('div.sensorUno span.temp2').text('.'+decimalTemp);
				
				if (data.rele_status=='1'){
					jQuery('div.statusBar .runing').addClass('on');
				}else{
					jQuery('div.statusBar .runing').removeClass('on');
				}
				
				if (data.current_mode=='day'){
					jQuery('div.statusBar .modeDay i').removeClass('fa-moon');
					jQuery('div.statusBar .modeDay i').addClass('fa-sun');
				}else{
					jQuery('div.statusBar .modeDay i').removeClass('fa-sun');
					jQuery('div.statusBar .modeDay i').addClass('fa-moon');
				}
				
				jQuery('div.humedad1').html('<i class="fas fa-tint"></i> '+roundHumedad+'%');
				
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
				jQuery('div.humedad2').html('<i class="fas fa-tint"></i> '+data.humedad+'%');
				window.setTimeout(myDomo.refreshExternalTemp,300000);
			},
			error:function(){
				window.setTimeout(myDomo.refreshExternalTemp,300000);
			}
		});
	},
	
	getDataTemp:function($func){
		jQuery.ajax({
			dataType:'json',
			url:'/index.php?module=getDataTemp',
			success:function(data){
				if (data.temp && data.temp.day){
					myDomo.dataTemperature=data
					$func();
				}else{
					console.log(data);
				}
			},
			error:function(error){
				console.log(error);
			}
		});
	},
	
	windowAjustTemp:function(){
		jQuery('.content').hide();
		jQuery('.content.windowAjustTemp').fadeIn();
		
		var today = new Date();
		var curWeekDay=today.getDay();
		curWeekDay--;
		if (curWeekDay<0){ curWeekDay=6;}
		
		myDomo.curWeekDay=curWeekDay;
		
		myDomo.getDataTemp(function(){
			myDomo.loadWeekDay();
			jQuery('input[name=tempNight]').val(myDomo.dataTemperature.temp.night);
			jQuery('input[name=tempDay]').val(myDomo.dataTemperature.temp.day);
		});
		
		jQuery('.dayAjust .btnDay').unbind('click').click(function(){
			myDomo.curWeekDay=jQuery(this).data('weekday');
			console.log('WeekDay: '+myDomo.curWeekDay);
			myDomo.loadWeekDay();
		});
	},
	
	loadWeekDay:function(){
		
		var $days=jQuery('div.dayAjust .btnDay').removeClass('active');
		$days.eq(myDomo.curWeekDay).addClass('active');
		
		confDay=myDomo.dataTemperature.days[myDomo.curWeekDay].split("");
		
		jQuery('.hourAjust .lineHour').each(function(key,obj){
			$btnHour=jQuery(obj);
			$btnHour.data('hour',key);
			
			if (confDay[key]==1){
				$btnHour.addClass('day');
			}else{
				jQuery(obj).removeClass('day');
			}
		});
	},
	
	windowPredicionAemet:function(){
		jQuery('.content').hide();
		script="<iframe id=\"iframe_aemet_id28141\" name=\"iframe_aemet_id28141\" src=\"http://www.aemet.es/es/eltiempo/prediccion/municipios/mostrarwidget/sevilla-la-nueva-id28141?w=g4p01110001ohmffffffw600z190x4f86d9t95b6e9r1s8n2\" width=\"100%\" height=\"190\" frameborder=\"0\" scrolling=\"no\"></iframe>";
		jQuery('.content.windowPredicionAemet .widget').html(script);
		jQuery('.content.windowPredicionAemet').fadeIn();
	},
	
	windowHistorial:function(){
		jQuery('.content').hide();
		jQuery.get('/?module=getHistory',function(data){
			jQuery('.content.windowHistory div.tableHistory').html(data);
			jQuery('.content.windowHistory').fadeIn();
		});
	},
	
	windowMain:function(){
		jQuery('.content').hide();
		jQuery('.content.windowMain').fadeIn();
	},
	
	btnUpClick:function(obj){
		$input=jQuery(obj).parent().find('input[type=number]');
		$input.val(parseInt($input.val())+1);
		myDomo.saveHoursWeekDay();
	},
	
	btnDownClick:function(obj){
		$input=jQuery(obj).parent().find('input[type=number]');
		$input.val(parseInt($input.val())-1);
		myDomo.saveHoursWeekDay();
	},
	
	lineHourClick:function(obj){
		$obj=jQuery(obj);
		$obj.toggleClass('day');
		confDay=myDomo.dataTemperature.days[myDomo.curWeekDay].split("");
		confDay[$obj.data('hour')]=($obj.hasClass('day') ? '1' : '0');
		myDomo.dataTemperature.days[myDomo.curWeekDay]=confDay.join('');
		myDomo.saveHoursWeekDay();
	},
	
	saveHoursWeekDay(){
		jQuery.ajax({
			dataType:'json',
			url:'/index.php?module=setDataTemp',
			data:{
				days:myDomo.dataTemperature.days,
				temp:{
					day:jQuery('input[name=tempDay]').val(),
					night:jQuery('input[name=tempNight]').val()
				}
			},
			success:function(data){
				if (data.ok){
					console.log('saved!');
				}else{
					console.log(data);
				}
			},
			error:function(error){
				console.log(error);
			}
		});
	}
	
};

jQuery(document).ready(function(){
	myDomo.init();
}); 
