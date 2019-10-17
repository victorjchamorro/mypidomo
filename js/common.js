/**
 * @author Victor J. Chamorro <victorjchamorro@gmail.com>
 *
 * LGPL v3 - GNU LESSER GENERAL PUBLIC LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU LESSER General Public License as published by
 * the Free Software Foundation, either version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU LESSER General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
var myDomo={
	
	dataTemperature:null,
	curWeekDay:null,
	timeoutSolar:null,
	flag:0,
	animationBattery:0,
	
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
		jQuery('.mainMenu button.menuSolar').click(function(){myDomo.windowSolar();myDomo.hideMenu();});
		jQuery('button.inversorOn').click(function(){myDomo.inversor('on');});
		jQuery('button.inversorOff').click(function(){myDomo.inversor('off');});
		
		jQuery('.windowSolar .btnChart').click(function(){myDomo.solarChart();});
		
		myDomo.refresh();
		myDomo.refreshExternalTemp();
		
		window.setInterval(function(){ myDomo.fecha_hora() }, 1000);
		setTimeout(function(){
		window.scrollTo(0, 1);
		}, 0);
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
	
	windowSolar:function(){
		jQuery('.content').hide();
		jQuery('.windowSolar .data').show();
		jQuery('.windowSolar .chart').hide();
		jQuery('.content.windowSolar').fadeIn();
		myDomo.refreshSolar();
	},
	
	refreshSolar:function(data){
		if (myDomo.timeoutSolar) clearTimeout(myDomo.timeoutSolar);
		var procesaRespuesta=function(data){
			jQuery('.content.windowSolar span.data-A').html('A: '+myDomo.numberFormat(data.a));
			jQuery('.content.windowSolar span.data-W').html('W: '+myDomo.numberFormat(data.w));
			if (myDomo.flag==0){
				jQuery('.content.windowSolar span.data-V').html('Vs:'+data.v);
				jQuery('.content.windowSolar span.data-P').html('Ap:'+myDomo.numberFormat(data.ap));
				myDomo.flag++;
			}else{
				jQuery('.content.windowSolar span.data-V').html('Vb:'+myDomo.numberFormat(data.vb));
				jQuery('.content.windowSolar span.data-P').html('Wl:'+myDomo.numberFormat(data.wp));
				myDomo.flag=0;
			}
			
			//1600W se considera 100% de cosecha
			porcentaje=Math.round(data.w*10000/1600)/100;
			
			//12.7 se considera 100% de batería
			//11.1 se considera 0% de batería
			//porcBateria=myDomo.scale(data.vb*100,11.1*2*100,12.7*2*100,0,100);
			porcBateria=data.soc;
			
			if (porcBateria>100){porcBateria=100;}
			
			if (porcentaje>40 || (data.w*1) > 100.0){
				jQuery('.content.windowSolar .imgSolar .estado').addClass('hide');
				jQuery('.content.windowSolar .imgSolar .sol').removeClass('hide');
			}else{
				if ((data.w*1) > 50){
					jQuery('.content.windowSolar .imgSolar .estado').addClass('hide');
					jQuery('.content.windowSolar .imgSolar .nublado').removeClass('hide');
				}else{
					jQuery('.content.windowSolar .imgSolar .estado').addClass('hide');
					jQuery('.content.windowSolar .imgSolar .noche').removeClass('hide');
				}
			}
			
			jQuery('.content.windowSolar span.data-porc').html(porcentaje>100 ? myDomo.numberFormat10(porcentaje) : myDomo.numberFormat(porcentaje));
			jQuery('.content.windowSolar span.data-pVb').html(porcBateria==100 ? "100.0" : myDomo.numberFormat(porcBateria));
			
			if (data.estado=="on"){ //Inversor ON
				jQuery('.content.windowSolar .actions i.red').addClass('hide');
				jQuery('.content.windowSolar .actions button.inversorOn').addClass('hide');
				jQuery('.content.windowSolar .actions button.inversorOff').removeClass('hide');
				//myDomo.batteryAnimate();
				jQuery('.content.windowSolar .actions .inversor').removeClass('hide');
			}else{ //Inversor Off
				jQuery('.content.windowSolar .actions i.red').removeClass('hide');
				jQuery('.content.windowSolar .actions .inversor').addClass('hide');
				jQuery('.content.windowSolar .actions button.inversorOn').removeClass('hide');
				jQuery('.content.windowSolar .actions button.inversorOff').addClass('hide');
			}
			
			myDomo.timeoutSolar=setTimeout(myDomo.refreshSolar,5000);
		}
		if (data){
			procesaRespuesta(data);
		}else{
			jQuery.getJSON('/?module=getSolar',procesaRespuesta);
		}
	},
	
	inversor:function(estado){
		jQuery.getJSON('/?module=inversor_'+estado,function(data){
			myDomo.refreshSolar();
		});
	},
	/*
	batteryAnimate:function(){
		jQuery('.content.windowSolar .actions i.inversor')
			.hide()
			.eq(myDomo.animationBattery)
			.delay(1000).show();
		myDomo.animationBattery++;
		if (myDomo.animationBattery>2){ 
			myDomo.animationBattery=0;			
		}else{
			window.setTimeout(myDomo.batteryAnimate,1000);
		}
	},*/
	
	windowMain:function(){
		if (myDomo.timeoutSolar) clearTimeout(myDomo.timeoutSolar);
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
	
	saveHoursWeekDay:function(){
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
	},
	
	solarChart:function(){
		jQuery.getJSON('/?module=getSolarHistory',function(data){
			jQuery('.windowSolar .data').hide();
			jQuery('.windowSolar .chart').show();
			//data={"labels":["14","15"],"series":[[153.30478666667,96.863201604278],[13.233666666667,13.204171122995]]};
			new Chartist.Line('.windowSolar .chart .chart-bateria', data.bateria);
			new Chartist.Line('.windowSolar .chart .chart-produccion', data.produccion);
		});
	},
	
	numberFormat:function(n){
		//if (n<0){n=0;}
		number=parseFloat(Math.round(n * 100) / 100).toFixed(2);
		if (n<0 && String(number).length==4) number='0'+String(number);
		return number;	
	},
	
	numberFormat10:function(n){
		if (n<0){n=0;}
		number=parseFloat(Math.round(n * 100) / 100).toFixed(1);
		if (String(number).length==3) number='0'+String(number);
		return number;	
	},
	
	scale:function(num, in_min, in_max, out_min, out_max){
		return (num - in_min) * (out_max - out_min) / (in_max - in_min) + out_min;
	}
};

jQuery(document).ready(function(){
	myDomo.init();
}); 
