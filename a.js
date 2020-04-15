function postchecklink(obj,panlink){
	jQuery.post(ajaxurl,{action:'pancheckstate',link:panlink},function(result){
		result = JSON.parse(result);
    	alert(result.msg);
    	if(result.status=='500'){
    		jQuery(obj).css("color","red");
    	}else{
    		jQuery(obj).css("color","green");
    	}
	}).fail(function(xhr,errorText,errorType){
		alert('请求接口失败，歇一会刷新下，然后再开始吧。');
	});
}
var onepostlink={};
var panstop = false;
var allstart = 0;
function updateprogress(){
	var start = Number(jQuery('#idstart').val());
	var end = Number(jQuery('#idend').val());
	var width = ((start-allstart)/(end-allstart)).toFixed(2) *100
	jQuery("#pangress").css("width",width+'%');
}
function panstopstart(){
	panstop = false
	checkall()
	jQuery("#panstart").hide()
	jQuery("#panstop").show()
	jQuery("#pangresstr").show()
	allstart = Number(jQuery('#idstart').val());
}
function panstopstop(){
	panstop = true
	jQuery("#panstart").show()
	jQuery("#panstop").hide()
	jQuery("#panstopping").show()
	jQuery("#pangresstr").hide()
}
function checkall(){
	if(panstop){
		jQuery("#panstopping").hide()
		jQuery("#pangresstr").hide()
		alert('暂停成功')
		return
	}
	var start = Number(jQuery('#idstart').val());
	var end = Number(jQuery('#idend').val());
	updateprogress()
	if(start<=end){
		setTimeout(() => {
			checkal(start);
		}, 500);
	}
}
function checkal(idd){
    jQuery.post(ajaxurl,{action:'pancheckall',id:idd},function(result){
		result = JSON.parse(result);
    	if(result.status=='200'){
    		onepostlink = result.msg;
    		postarray();
    	}
	}).fail(function(xhr,errorText,errorType){
		alert('请求接口失败，歇一会刷新下，然后再开始吧。');
	});
}
function postarray(){
	if(onepostlink.length !== 0){
		setTimeout(() => {
			checkonepost(onepostlink.pop(),Number(jQuery('#idstart').val()));
		}, 1000);
	}else{
		var start = Number(jQuery('#idstart').val());
		var end = Number(jQuery('#idend').val());
		if(start<end){
			start++;
    		jQuery('#idstart').val(start);	
			checkall();
		}else{
			jQuery("#panstart").show()
			jQuery("#panstop").hide()
			jQuery("#pangresstr").hide()
			alert('检测完成')
		}
	}
}
function checkonepost(panlink,postid){
	jQuery.post(ajaxurl,{action:'pancheckonepost',link:panlink,id:postid},function(result){
		result = JSON.parse(result);
    	if(result.status=='200'){
    		jQuery('#checklist').append(result.msg);
    		postarray();
    	}
	}).fail(function(xhr,errorText,errorType){
		alert('请求接口失败，歇一会刷新下，然后再开始吧。');
	});
}