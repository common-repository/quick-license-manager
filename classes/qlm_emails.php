<?php
if ( !class_exists('QLM_Emails')){
	class QLM_Emails{
		
		function __construct(){
			
			
		}
		
		function show_default_footer(){
			ob_start();

			?>

			<div style = "">

				<h3>Powered by QLM </h3><br/>

			</div>

			<?php

			$html = ob_get_clean();

			return $html;
		}
		
		function show_default_header(){
			ob_start();

			?>

			<div style = "">

				<h3>Hi #customername#, </h3><br/>

			</div>

			<?php

			$html = ob_get_clean();

			return $html;
		}

		function show_default_subject(){
			ob_start();

			?>Order ID: #order_id#<?php

			$html = ob_get_clean();

			return $html;
		}
		
		//Shows email tempaltes in admin
		function show_email_templates($qlm_products){
			$qml_product_id = '';
			if(isset($_POST['submit_qlm_products'])){
				$qml_product_id = $_POST['submit_qlm_products'];
			}
			?>
			<div class="tw-bs container" style=""><!-- start container -->
				<form method="post" action="">
					<div class="row">
						<div class="col-md-12" style="margin-top:1em;" >
							<h1>Email Templates</h2>
						</div>
					</div>
					<!-- Subject -->
					<div class="row">
						<div class="col-md-10" style="margin-top:1em;" >
							<h2 style="text-align:center;">Subject</h2>
						</div>
					</div>
					<div class="col-md-2" >&nbsp;</div>

					<div class="row">
						<div class="col-md-10" >
							<?php
							
							$default_subject=$this->show_default_subject();
							$email_template = get_option( 'qlm_subject', $default_subject );
							$editor_id='qlm_html_subject';
							$settings = array( 'media_buttons' => false,											   
											   'textarea_rows' => '1',
											   'tinymce'=> false,
											   'teeny' => true
							);
							
							wp_editor( $email_template, $editor_id, $settings);
							
							?>
							
						</div>
						<div class="col-md-2" >&nbsp;</div>
					</div>
					<!-- Subject -->

					<div class="row">
						<div class="col-md-10" style="margin-top:1em;" >
							<h2 style="text-align:center;">Header</h2>
						</div>
					</div>
					<div class="col-md-2" >&nbsp;</div>

					<div class="row">
						<div class="col-md-10" >
							<?php
							
							$default_template=$this->show_default_header();
							$email_template = get_option( 'qlm_header', $default_template );
							$editor_id='qlm_html_header';
							$settings = array( 'media_buttons' => false,
											   'editor_height' => '200px'
							);
							
							wp_editor( $email_template, $editor_id, $settings);
							
							?>
							
						</div>
						<div class="col-md-2" >&nbsp;</div>
					</div>
					<div class="row">
						<div class="col-md-12" ><h3>Hashtags List</h3></div>	
					</div>	
					<div class="row">
						<div class="col-md-12" >
							<ul>
								<li>#customername#</li>
							</ul>
						</div>
					</div>
					<div class="row">
						<div class="col-md-10" style="margin-top:1em;" >
							<h1 style="text-align:center;">Body</h1>
						</div>
					</div>
					<div class="row">
						<div class="col-md-10" >
							<div style="width:28em;">
								<select class="form-control" required name="submit_qlm_products" onchange="this.form.submit()">
									<?php if ($qml_product_id){?>
									<option value="<?php echo $qml_product_id ?>"><?php echo get_the_title($qml_product_id); ?></option>
									<?php } else { ?>
									<option value="<?php  ?>">Select Product</option>
									<?php }
									foreach($qlm_products as $p){
										$product_id=$p->ID;
										$product_title=$p->post_title;
										?><option value="<?php echo $product_id; ?>"><?php echo $product_title;?></option> <?php
									}
									?>
								</select>
							</div>
						</div>
						<div class="col-md-2" >&nbsp;</div>
					</div>
					<?php
					if (!empty($qml_product_id)){ ?>
						<div class="row">
							<div class="col-md-10" >
								<?php
								$email_template = get_post_meta($qml_product_id,'qlm_email_html',true);
								$editor_id='qlm_html_mail';
								$settings = array( 'media_buttons' => false);
								
								if($email_template){
									wp_editor( $email_template, $editor_id, $settings);
								}else{
									$default_template=$this->show_default_template_html();
									wp_editor( $default_template, $editor_id, $settings);
								} ?>
								<input type="hidden" value="<?php echo $qml_product_id;?>" name="qlm_pid">
							</div>
							<div class="col-md-2" >&nbsp;</div>
						</div>
						<div class="row">
							<div class="col-md-12" ><h3>Hashtags List</h3></div>	
						</div>	
						<div class="row">
							<div class="col-md-12" >
								<ul>
									<li>#customername#</li>
									<li>#product#</li>
									<li>#LicenseKeys#</li>
								</ul>
							</div>
						</div>
						
					<div class="row">
						<div class="col-md-10" style="margin-top:1em;" >
							<h2 style="text-align:center;">Footer</h2>
						</div>
					</div>
					<div class="col-md-2" >&nbsp;</div>
					<div class="row">
						<div class="col-md-10" >
							<?php
							
							$default_footer = $this->show_default_footer();
							$email_template = get_option( 'qlm_footer', $default_footer );
							$editor_id='qlm_html_footer';
							$settings = array( 'media_buttons' => false,
												'editor_height' => '200px'							
										);
							
							wp_editor( $email_template, $editor_id, $settings);
							
							?>
						</div>
						<div class="col-md-2" >&nbsp;</div>
					</div>
					<div class="row">
							<div class="col-md-12" style="margin-top:1em;">
								<input type="submit" name="submit_template" value="Update" class="btn btn-primary btn-lg" style="width:10em" />
							</div>
						</div>
			<?php 	} ?>
				</form>
			</div>	<!-- end container -->
		<?php	
		}
		
		function get_html_of_each_product($order, $product_id, $order_id, $license_keys)
		{
			if (QLM_DEBUG)
            {
			    $order->add_order_note('QLM - Updating Email for product id:'.$product_id.' and license key:'.$license_keys);
			}

			$product_title = get_the_title($product_id);

			//$user = get_userdata();
			//$user_name = $user->display_name;
			//$user_email = $user->user_email;

			$user_name = $order->billing_first_name.' '.$order->billing_last_name;
			$user_email = $order->billing_email;

			$email_template = get_post_meta($product_id,'qlm_email_html',true);

			if(empty($email_template))
			{
				$email_template = $this->show_default_template_html();
			}

			if (QLM_DEBUG)
            {
			    $order->add_order_note('QLM - Updating Email - getting license key for product id:'.$product_id);
			}
			
			//$license_keys = get_post_meta( $order_id, '_qlm_license_keys', true );	

			if (QLM_DEBUG)
            {			    
                $order->add_order_note('QLM - Updating Email for license key2:'.$license_keys);
            }

			$email_template = str_replace( '#customername#' ,$user_name, $email_template );
			$email_template = str_replace( '#product#' ,$product_title, $email_template );
			$email_template = str_replace( '#LicenseKeys#' ,$license_keys, $email_template );
			
			return $email_template;
		}
		
		/*
			Sends email for an order
			$user_id  user id of current customer
			$order_id  order id of current order
		*/
		function send_email($order, $email, $send_email_html, $email_subject)
		{
			if (QLM_DEBUG)
            {			    
                $order->add_order_note('QLM - Sending mail to: '.$email);
            }	

			add_filter( 'wp_mail_content_type',array(&$this,'wp_set_content_type' ));
			$send_mail = wp_mail( $email, $email_subject, $send_email_html );
			remove_filter( 'wp_mail_content_type', array(&$this,'wp_set_content_type' ));
		}
		
		//wp function 
		function wp_set_content_type(){

			return "text/html";

		}
		
		//It is default email templates and will be used if none has been created by admin
		function show_default_template_html(){

			ob_start();

			?>

			<div style = "">

					Thank you for purchasing #product#.<br/>

					Your licence key is: #LicenseKeys#<br/><br>					

			</div>

			<?php

			$html = ob_get_clean();

			return $html;

		}

		function nmt_quicktags_buttons( $qt_init) 
		{
			$del_buttons = array('del','ins','img','code');
			$qt_init['buttons'] = implode(',', array_diff(explode(',',$qt_init['buttons']), $del_buttons));
			return $qt_init;
		}	
		
		
	}// calss ends
}//if ends

