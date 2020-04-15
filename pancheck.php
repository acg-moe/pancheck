<?php
/*
Plugin Name: uk_checkpan
Description: 检查文章内部百度链接是否有效（seven b2通用）
Author: 3xs
Version: 1.6
Author URI: https://www.affme.cn/
*/
function add_panscheck_column($columns) {   
    $columns['panscheck'] = '资源列表';   
    return $columns;   
}   
add_filter('manage_posts_columns' , 'add_panscheck_column');
function panscheck_column_content($column_name, $post_id) {   
    if ($column_name == 'panscheck') {   
        $panscheck_value = get_download_url( $post_id );   
        echo $panscheck_value;
    }   
}   
add_action('manage_posts_custom_column', 'panscheck_column_content', 10, 2); 
function get_download_url($post_id,$array=false){
    $arg = array();
	$post_content = get_post_field('post_content',$post_id);
	$regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
	preg_match_all($regex, $post_content, $matches);	
    foreach ($matches[0] as $k => $v) {
	    if(strpos($v,'pan.baidu.com') !== false){
	    	$arg[] = $v;	
	    }
    }

	//日主题下载
	$cao_url = get_post_meta($post_id,'cao_downurl',true);
	if($cao_url){
	    if(strpos($cao_url,'pan.baidu.com') !== false){
	    	$arg[] = $cao_url;
	    }
	}
	$cao_bak_url = get_post_meta($post_id,'cao_downurl_bak',true);
	if($cao_bak_url){
	    if(strpos($cao_bak_url,'pan.baidu.com') !== false){
	    	$arg[] = $cao_bak_url;
	    }
	}
	


    if(defined('B2_THEME_URI')){//兼容b2
        if(get_post_meta($post_id,'b2_open_download',true)){//下载功能
        	$download_settings = get_post_meta($post_id,'b2_single_post_download_group',true);
        	if($download_settings && is_array($download_settings)){
		        foreach($download_settings as $k => $v){
        	        $str = trim($v["url"], " \t\n\r\0\x0B\xC2\xA0");
			        $str = explode(PHP_EOL, $str );
			        foreach ($str as $k => $v) {
			            $_v = explode('|', $v);
			            if(!isset($_v[0]) && !isset($_v[1])) continue;
			            if(strpos($_v[1],'pan.baidu.com') !== false){
	                		$arg[] = $_v[1];	
	                	}
			        }
		        }
        	}
        }
	    
    }
    if($array){
    	return $arg;
    }
    $html = '';
    foreach ($arg as $a){
    	$html .= '<span id="ukpanlink" style="cursor: pointer;" onclick="postchecklink(this,\''.$a.'\')">'.$a.'</span><br>';
    }
    return $html;
}
function pan_check_link($url){
	//http://pan.baidu.com/share/link?shareid=1854596897&uk=607607162 无效
	//https://pan.baidu.com/s/1EjDkmFbSCB40rptyGPl_CA 分享取消
	//https://pan.baidu.com/s/1K_olXAsAeO-oPjZf7aQUpA 侵权
	//https://pan.baidu.com/share/init?surl=qXQD2Pm 密码
	//https://pan.baidu.com/s/1YLuwZIcesmhvFh33cYfHxQ 正常
	$link = $url;
	if($link == '链接为空'){
		return array('status'=>'500','msg' =>'存在空链接');
	}
	$response = wp_remote_get( $link );
	if ( is_array( $response ) && !is_wp_error($response) && $response['response']['code'] == '200' ) {
		$header = $response['headers'];
		$body = $response['body'];
		if(strpos($body,'页面不存在') !== false){ 
			return array('status'=>'500','msg' =>'页面不存在');
		}
		if(strpos($body,'链接不存在') !== false){ 
			return array('status'=>'500','msg' =>'链接不存在');
		}
		if(strpos($body,'此链接分享内容可能因为涉及侵权、色情、反动、低俗等信息，无法访问') !== false){ 
			return array('status'=>'500','msg' =>'已封禁');
		}
		if(strpos($body,'啊哦，来晚了，该分享文件已过期') !== false){ 
			return array('status'=>'500','msg' =>'文件已过期');
		}
		if(strpos($body,'分享的文件已经被取消了') !== false){ 
			return array('status'=>'500','msg' =>'文件分享已取消');
		}
		if(strpos($body,'分享的文件已经被删除了') !== false){ 
			return array('status'=>'500','msg' =>'文件分享已删除');
		}
		if(strpos($body,'给您加密分享了文件') !== false){ 
			return array('status'=>'200','msg' =>'加密分享文件');
		}
		return array('status'=>'200','msg' =>'正常');
	}else{
		return array('status'=>'500','msg' =>'请求失败');
	}
}

add_action( 'admin_enqueue_scripts', 'pansetup_admin_scripts' );
function pansetup_admin_scripts(){
	  $plugin_data = get_plugin_data( __FILE__ );
	  $plugin_version = $plugin_data['Version'];
    wp_enqueue_script( 'pancheck_admin_js',home_url('/wp-content/plugins/pancheck/a.js'), array('jquery'), $plugin_version, true );
    wp_enqueue_style( 'pancheck_admin_css', home_url('/wp-content/plugins/pancheck/a.css'), array(), null);
}

function pancheckstate() {
	if(!is_super_admin()){
		print json_encode(array('status'=>500,'msg' =>'非管理员'));
        die();
	}
	$url = isset($_POST['link']) ? esc_url($_POST['link']) : '';
	$state = pan_check_link($url);
	print json_encode($state);
    die();
}
add_action( 'wp_ajax_nopriv_pancheckstate', 'pancheckstate' );
add_action( 'wp_ajax_pancheckstate', 'pancheckstate' );

function pancheckall() {
	if(!is_super_admin()){
		print json_encode(array('status'=>500,'msg' =>'非管理员'));
        die();
	}
	$post_id = isset($_POST['id']) ? intval($_POST['id']) : '';
	$msg = array();
	if(get_permalink($post_id)!==false && get_post_status($post_id)==='publish'){
	    $msg = get_download_url($post_id,true);
	}
	print json_encode(array('status'=>'200','msg' =>$msg));
    die();
}
add_action( 'wp_ajax_nopriv_pancheckall', 'pancheckall' );
add_action( 'wp_ajax_pancheckall', 'pancheckall' );

function pancheckonepost(){
	if(!is_super_admin()){
		print json_encode(array('status'=>500,'msg' =>'非管理员'));
        die();
	}
	$url = isset($_POST['link']) ? esc_url($_POST['link']) : '';
	$post_id = isset($_POST['id']) ? intval($_POST['id']) : '';
	$post_title = get_the_title($post_id);
	$check = pan_check_link($url);
	if($check['status']=='500'){
		$postlink=home_url('/wp-admin/post.php?post='.$post_id.'&action=edit');
		$msg .= '<div style="color:red;">
					<span class="mar10">文章ID：'.$post_id.'</span>
					<span class="mar10">文章标题：'.$post_title.'</span>
					<span class="mar10">问题链接：'.$url.'</span>
					<span class="mar10">问题描述：'.$check['msg'].'</span>
					<span><a href="'.$postlink.'" target="_blank">跳转编辑</a></span>
				</div>';
	}else{
		$msg .= '<div style="color:green;">
		<span class="mar10">文章ID：'.$post_id.'</span>
		<span class="mar10">文章标题：'.$post_title.'</span>
		<span class="mar10">资源链接：'.$url.'</span>
		<span class="mar10">正常</span></div>';
	}
	print json_encode(array('status'=>'200','msg' =>$msg));
    die();
}
add_action( 'wp_ajax_nopriv_pancheckonepost', 'pancheckonepost' );
add_action( 'wp_ajax_pancheckonepost', 'pancheckonepost' );


function pancheck_menu_page() {
	$title = esc_html__('网盘链接检查', '网盘链接检查');
	add_menu_page($title, $title, 'manage_options', 'pancheck_settings', 'pancheck_display_settings');
}
add_action('admin_menu', 'pancheck_menu_page');

function pancheck_display_settings(){
?>
<style>
	.mar10{
		margin: 10px;
	}
.progress {
	background-color: rgba(100, 100, 100, 0.2);
	border-radius: 5px;
	position: relative;
	height: 10px;
	width: 200px;
}

.progress-done {
	background: linear-gradient(to left, rgb(242, 112, 156), rgb(255, 148, 114));
	box-shadow: 0 3px 3px -5px rgb(242, 112, 156), 0 2px 5px rgb(242, 112, 156);
	border-radius: 5px;
	height: 10px;
	width: 0;
	transition: width 1s ease 0.3s;
}
</style>
<div id="panstopping" style="display:none;padding:11px 15px;margin: 5px 15px 2px 2px;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);background:#fff;border-left:4px solid green">正在暂停中。。</div>
<div class="wrap">
	<h2 class="title">对文章内附件网盘资源是否有效进行检查</h2>
	<p>点击一次，然后请耐心等待</p>
	<p>输入文章ID范围时请量力而行（ps,无效ID会自动排除,仅检查已发表文章）</p>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="start">开始文章ID</label></th>
				<td><input name="start" class="regular-text" type="text" id="idstart" placeholder="开始检查范围的文章ID"></td>
			</tr>
			<tr>
				<th scope="row"><label for="end">结束文章ID</label></th>
				<td><input name="end" class="regular-text" type="text" id="idend" placeholder="结束检查范围的文章ID"></td>
			</tr>
			<tr id="pangresstr" style="display:none">
				<th scope="row">
					<div class="progress" >
						<div id="pangress" class="progress-done" style="width: 0%;"></div>
					</div>
				</th>
			</tr>
			<tr>
				<th scope="row">
					<button id="panstart" class="button button-primary" onclick="panstopstart()">开始</button>
					<button id="panstop" class="button button-primary" onclick="panstopstop()" style="display:none">暂停</button>
				</th>
			</tr>
		</tbody>
	<table>
	<h2 class="title">检测到的资源列表</h2>	
	<div id="checklist">
	</div>

</div>
<?php
}