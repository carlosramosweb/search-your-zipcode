<?php
/*---------------------------------------------------------
Plugin Name: Search Your ZipCode
Author: carlosramosweb
Author URI: https://criacaocriativa.com
Donate link: https://donate.criacaocriativa.com
Description: Esse plugin é uma versão BETA. Sistema de busca de CEPs por assinatura de plano de internet no WordPress.
Text Domain: search-your-zipcode
Domain Path: /languages/
Version: 2.1.0
Requires at least: 3.5.0
Tested up to: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html 
------------------------------------------------------------*/

/*
 * Exit if the file is accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'Search_Your_ZipCode' ) ) {	
	class Search_Your_ZipCode {
		//..
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_functions' ) );
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
		}
		//=>

		public function init_functions() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links_settings' ) );
			add_action( 'init', array( $this, 'wp_register_posttype_custom' ) );
			add_action( 'admin_menu', array( $this, 'wp_register_menu_item_admin' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'wp_register_submenu_item_admin' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'wp_register_submenu_add_item_admin' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'wp_register_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'wp_save_meta_box' ), 10, 2 );
			add_action( 'wp_ajax_search_your_zipcode_callback', array( $this, 'search_your_zipcode_callback' ) );
			add_action( 'wp_ajax_nopriv_search_your_zipcode_callback', array( $this, 'search_your_zipcode_callback' ) );
			add_action( 'wp_ajax_report_search_your_zipcode_callback', array( $this, 'report_search_your_zipcode_callback' ) );
			add_action( 'wp_ajax_nopriv_report_search_your_zipcode_callback', array( $this, 'report_search_your_zipcode_callback' ) );
			add_shortcode( 'search_your_zipcode', array( $this, 'search_your_zipcode_shortcode_callback' ) );
		}
		//=>

		public function plugin_links_settings( $links ) {
			$action_links = [];
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=search_zipcode' ) . '" title="Configuracões" class="edit">Configuracões</a>',
			);
			return array_merge( $action_links, $links );
		}
		//=>

		public function wp_register_meta_boxes() {
			add_meta_box( 
				'meta-box-id', 
				'Configurações', 
				array( $this, 'wp_register_meta_boxes_display_callback' ), 
				'search_zipcode',
				'advanced',
				'default'
			);
		}
		//=>

		public function wp_register_meta_boxes_display_callback( $post ) {
		    global $wpdb;
		    $post_id 		= $post->ID;
		    $postmetas 		= get_post_meta( $post_id, '_search_your_zipcode', true );
			$link_plan 		= get_post_meta( $post_id, '_search_your_zipcode_link_plan', true ); 
			$date_postmetas = get_post_meta( $post_id, '_search_your_date_zipcode', true );

			$total_zipcodes = 0;
			if ( isset( $postmetas ) && ! empty( $postmetas ) ) {
				$total_zipcodes = count( $postmetas );
			}
			$subtotal 		= "";
			if ( isset( $_GET['zipcodes'] ) && ! empty( $_GET['zipcodes'] ) ) {
				$get_zipcodes = ( $_GET['zipcodes'] - 1 );
			}
			if ( ! empty(  $total_zipcodes ) && $total_zipcodes > 100 ) {
				$subtotal_zipcodes = ( $total_zipcodes / ( $total_zipcodes / 100 ) );
				$postmetas = array_chunk( $postmetas, $subtotal_zipcodes );
				$subtotal = "1";
			}
			if ( isset( $date_postmetas ) && empty( $date_postmetas ) ) {
				$date_postmetas = 0;
			}
			?>
            <table class="form-table">
                <tbody>
                    <!---->
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Link do Plano
                            </label>
                        </th>
                        <td>
                            <label>
                            	<input type="text" name="link_plan" style="width: 100%;" value="<?php echo $link_plan; ?>" placeholder="http:// ou https://">
                            </label>
                       </td>
                    </tr>  
                    <!----->
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                CEP
                            </label>
                        </th>
                        <td>
                            <label>
                            	<input type="text" name="zipcode" class="input_zipcode" style="width: 50%;" value="">
                            </label>
                            <input type="submit" name="save" id="publish" class="button button-primary button-large" value="Adicionar">
                       </td>
                    </tr>  
                    <!----->
                    <tr valign="top">
                        <td colspan="2" style="padding: 0;">
                            <hr/>
                       </td>
                    </tr>  
                    <!----->
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Lista de CEPs: <br/>
                                Total <?php echo $total_zipcodes; ?> iten(s)<br/>
                                <?php if( $subtotal == "1" ) { ?>
                                Listando <?php echo count( $postmetas[$get_zipcodes] ); ?> iten(s)<br/>
                            	<?php } ?>
                            </label>
                        </th>
                        <td>
                            <ul>
                            <?php 
                            if( $total_zipcodes > 0 ) {
                            	$total_postmetas = $postmetas;
                            	if( $subtotal == "1" ) {
                            		$total_postmetas = $postmetas[$get_zipcodes];
                            	}
                                foreach ( $total_postmetas as $key => $cep ) { ?>
                                    <li class="item_zipcode zipcode-<?php echo $cep; ?>">
                                    	<?php echo $cep; ?>
                                        <a href="admin.php?page=search_zipcode&post_id=<?php echo $post_id; ?>&remove_zipcode=<?php echo $cep; ?>&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'remove-zipcode' ) ); ?>" class="btn_remove_zipcode">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                        <span style="clear: both;"></span>
                                    </li>
                                    <?php
                                } 
                            } else { ?>
                            <li>Ainda não tem CEP cadastrado nesse post.</li>
                            <?php } ?>
                            </ul>
                            <?php
                            if( $subtotal == "1" ) { 
                            	$post_id = $_GET['post'];

								$get_zipcodes = "1";
								if ( isset( $_GET['zipcodes'] ) ) {
									$get_zipcodes = $_GET['zipcodes'];
								}

								$prev_current 	= "1";
                        		$prev_item 		= ( $get_zipcodes - 1 );
                        		$next_item 		= ( $get_zipcodes + 1 );

                            	if( $get_zipcodes == "" && $get_zipcodes == "0" ) { 
                            		$get_zipcodes 	= "1";
                            		$prev_item 		= "1";
                            		$next_item 		= "2";
                            	}
                            	$prev_page = esc_url( admin_url( "post.php?post={$post_id}&action=edit&zipcodes=" . $prev_item  ) );
                            	$next_page = esc_url( admin_url( "post.php?post={$post_id}&action=edit&zipcodes=" .  $next_item ) );

                            	if( $prev_item == "0" || $prev_item == "1" || $prev_item == "" ) { 
                            		$prev_page = esc_url( admin_url( "post.php?post={$post_id}&action=edit" ) );
                            		$prev_current = ( $next_item - 1 );
                            	}

                            	if( $next_item > 2 ) { 
                            		$prev_current = ( $next_item - 1 );
                            	}
                            	?>
                            	<hr/>
								<div class="tablenav-pages">
									<a class="prev-page button" href="<?php echo $prev_page; ?>">
										<span class="screen-reader-text">Página anterior</span>
										<span aria-hidden="true">‹</span>
									</a>
									<span class="pagination-links">
										<span class="tablenav-paging-text"><?php echo $prev_current; ?> de 
											<span class="total-pages"><?php echo count( $postmetas ); ?></span>
										</span>
									</span>
									<a class="next-page button" href="<?php echo $next_page; ?>">
										<span class="screen-reader-text">Próxima página</span>
										<span aria-hidden="true">›</span>
									</a>
								</div>
                            <?php } ?>
                       </td>
                    </tr>  
                    <!----->
               </tbody>
            </table>
            <br/><hr/><br/>
            <table class="form-table">
                <tbody>
                    <!---->
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Data
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="text" name="date_zipcode" class="input_date_zipcode" style="width: 50%;" value="">
                            </label>
                            <input type="submit" name="save" id="publish" class="button button-primary button-large" value="Adicionar">
                       </td>
                    </tr>  
                    <!----->
                    <tr valign="top">
                        <td colspan="2" style="padding: 0;">
                            <hr/>
                       </td>
                    </tr>  
                    <!----->
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Lista de Datas
                            </label>
                        </th>
                        <td>
                            <ul>
                            <?php
                            if( $date_postmetas != 0 && count( $date_postmetas ) > 0 ) {
                                    foreach ( $date_postmetas as $key => $date ) { 
                                        $br_date    = date( "d/m/Y", strtotime( $date ) );
                                        ?>
                                        <li class="item_date_zipcode date_zipcode-<?php echo $date; ?>">
                                            <?php echo $br_date; ?>
                                            <a href="admin.php?page=search_zipcode&post_id=<?php echo $post_id; ?>&remove_date_zipcode=<?php echo $date; ?>&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'remove-date-zipcode' ) ); ?>" class="btn_remove_date_zipcode">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                            <span style="clear: both;"></span>
                                        </li>
                                        <?php
                                    } 
                                } else { ?>
                                <li>Ainda não tem data cadastrado nesse post.</li>
                            <?php } ?>
                            </ul>
                       </td>
                    </tr>  
                    <!----->
               </tbody>
            </table>

            <style>
                .item_zipcode { display: block; padding: 5px 10px; border: 1px solid #CCC; margin: 0; }
                .item_zipcode:hover { background-color: #e7e7e7; }
                .item_zipcode a.btn_remove_zipcode { display:block; float: right; color: #000; margin:0px 10px; cursor: pointer; text-decoration: none; }
                .item_zipcode a.btn_remove_zipcode:hover { color: #cc0000; }
                .item_date_zipcode { display: block; padding: 5px 10px; border: 1px solid #CCC; margin: 0; }
                .item_date_zipcode:hover { background-color: #e7e7e7; }
                .item_date_zipcode a.btn_remove_date_zipcode { display:block; float: right; color: #000; margin:0px 10px; cursor: pointer; text-decoration: none; }
                .item_date_zipcode a.btn_remove_date_zipcode:hover { color: #cc0000; }
            </style>
			<script src="//code.jquery.com/jquery-3.5.1.js"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
			<script type="text/javascript">
			$(document).ready(function(){
				$( ".input_zipcode" ).mask("99.999-999");
				$( ".input_date_zipcode" ).mask("99/99/9999");
			});
			</script>
			<?php
		}
		//=>

		public function wp_save_meta_box( $post_id ) {
		    $zipcode_settings = get_post_meta( $post_id, '_search_your_zipcode', true );
		    $link_plan 	= '';
		    if ( isset( $_POST['link_plan'] )  ) {
				$link_plan 	= $_POST['link_plan']; 
		    }
		    $zipcode = '';
		    if ( isset( $_POST['zipcode'] ) ) {
				$zipcode 	= $_POST['zipcode'];
		    }					
			if( ! empty( $zipcode ) ) {
				$zipcode 	= str_replace( '.', '', $zipcode );
				$zipcode 	= str_replace( '-', '', $zipcode );	
    			if( empty( $zipcode_settings ) ) {
    			    $zipcode_settings = array(
    			        $zipcode,
    			    );
    			    update_post_meta( $post_id, '_search_your_zipcode', $zipcode_settings );
    			} else {
    			    array_push( $zipcode_settings, $zipcode );
    			    update_post_meta( $post_id, '_search_your_zipcode', $zipcode_settings );
    			}
			}
			update_post_meta( $post_id, '_search_your_zipcode_link_plan', $link_plan );

			$date_zipcode_settings = get_post_meta( $post_id, '_search_your_date_zipcode', true );
            $date_zipcode 	= '';
            if ( isset( $_POST['date_zipcode'] ) ) {
				$date_zipcode 	= $_POST['date_zipcode'];
            }
			if( ! empty( $date_zipcode ) ) {
				$date_zipcode 	= str_replace( '/', '-', $date_zipcode );
				$date_zipcode 	= date( "Y-m-d", strtotime( $date_zipcode ) );
    			if( empty( $date_zipcode_settings ) ) {
    			    $date_zipcode_settings = array(
    			        $date_zipcode,
    			    );
    			    update_post_meta( $post_id, '_search_your_date_zipcode', $date_zipcode_settings );
    			} else {
    			    array_push( $date_zipcode_settings, $date_zipcode );
    			    update_post_meta( $post_id, '_search_your_date_zipcode', $date_zipcode_settings );
    			}
			}
		}
		//=>

		public static function activate_plugin() {
			$settings = get_option( 'search_your_zipcode_settings' );
			if ( empty( $settings ) ) {
				$settings = array(
					'enabled'			=> "yes",
					'text_system'		=> "yes",
					'text_custom'		=> "yes",
					'modal_system'		=> "yes",
					'message_system'	=> "yes",
					'url_contact'		=> "#",
				);
				update_option( 'search_your_zipcode_settings', $settings );
			}
		}
		//=>

		public function wp_register_posttype_custom() {
		    $args = array(
		        'public'    			=> true,
		        'label'     			=> 'Zonas de CEPs',
		        'capability_type' 		=> 'post',
		        'public_queryable'		=> true,
		        'show_ui'				=> true,
		        'show_in_menu'			=> false,
		        'show_in_nav_menus'		=> true,
		        'show_in_admin_bar'		=> true,
		        'supports'				=> array( 'title', 'editor', 'thumbnail' ), 
		    );
		    register_post_type( 'search_zipcode', $args );
		}
		//=>

		public function wp_register_menu_item_admin() {
			add_menu_page(
		        'Configurações',
		        'CEPs Disponíveis',
		        'edit_posts',
		        'search_zipcode',
		        array( $this, 'wp_page_admin_settings_callback' ),
		        'dashicons-feedback',
		        10
		    );
		}
		//=>

		public function wp_register_submenu_item_admin() {
			add_submenu_page(
				'search_zipcode',
				'Lista Completa', 
				'Lista Completa', 
				'edit_posts', 
		        'edit.php?post_type=search_zipcode'
			);
		}
		//=>

		public function wp_register_submenu_add_item_admin() {
			add_submenu_page(
				'search_zipcode',
				'Adicionar Novo', 
				'Adicionar Novo', 
				'edit_posts', 
		        'post-new.php?post_type=search_zipcode'
			);
		}
		//=>

		public function search_your_zipcode_shortcode_callback() {
			$settings = get_option( 'search_your_zipcode_settings' );
			?>
			<div id="form_zipcode" class="form_zipcode">
				<?php if ( $settings['text_system'] == "yes" ) { ?>
				<h2><strong>Pesquise seu CEP</strong></h2>
				<p>Veja aqui se atendemos na sua região.<br/>
				Insira o cep na caixa de pesquisa abaixo.</p>
				<?php } ?>
				<div class="box_name">
					<input type="text" name="name" class="input_name" placeholder="Digite seu Nome">
					<div style="clear: both;"></div>
				</div>
				<div class="empty_name" style="display: none;">
					<p class="msg_empty_name">
						<i>O Nome está vazio ou incompleto.</i>
					</p>
				</div>
				<div class="box_email">
					<input type="text" name="email" class="input_email" placeholder="Digite seu E-mail">
					<div style="clear: both;"></div>
				</div>
				<div class="empty_email" style="display: none;">
					<p class="msg_empty_email">
						<i>O E-mail está vazio ou incompleto.</i>
					</p>
				</div>
				<div class="box_whatsapp">
					<input type="tel" name="whatsapp" class="input_whatsapp" min="11" max="11" placeholder="Digite seu WhatsApp">
					<div style="clear: both;"></div>
				</div>
				<div class="empty_whatsapp" style="display: none;">
					<p class="msg_empty_whatsapp">
						<i>O WhatsApp está vazio ou incompleto.</i>
					</p>
				</div>
				<div class="box_search">
					<input type="text" name="zipcode" class="input_zipcode" placeholder="Digite seu CEP">
					<button class="btn button submit_zipcode">
						<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_search.png" alt="icon search" class="icon_search_zipcode">
					</button>
					<div style="clear: both;"></div>
				</div>
				<div class="empty_zipcode" style="display: none;">
					<p class="msg_empty_zipcode">
						<i>O CEP está vazio ou incompleto.</i>
					</p>
				</div>
				<div class="search_zipcode" style="display: none;">
					<p class="msg_loading_zipcode">
						<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_loading.gif" alt="icon loading" class="icon_loading_zipcode">
						<i>Pesquisando...</i>
					</p>
				</div>
				<!--Box--->
				<div class="modal_system" style="display: none;">
					<?php if ( $settings['modal_system'] == "yes" ) { ?>
					<div class="btn_modal_system">
						<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_close.png" alt="icon close" class="btn_modal_close">
						<div style="clear: both;"></div>
					</div>
					<?php } ?>
					<!--Box Error--->
					<div class="box_error_zipcode" style="display: none;">
						<p class="msg_error_zipcode">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_error.png" alt="icon error" class="icon_error_zipcode">
							<span>Desculpe, no momento o seu CEP não está em nossa área de cobertura.
							<?php if ( $settings['url_contact'] != "" ) { ?>
							<br/>Você pode entrar em <a href="<?php echo $settings['url_contact']; ?>"><strong>Contato Conosco</strong></a> e verificar se existem futuras expansões para esta região.</span>
							<?php } ?>
							<br/><br/><button type="button" class="btn btn-danger btn-report-zipcode">Enviar sugestão de CEP</button>
						</p>
					</div>
					<!--Box Report Success--->
					<div class="box_report_success_zipcode" style="display: none;">
						<p class="msg_success_zipcode">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_success.png" alt="icon error" class="icon_success_zipcode">
							<span>
								<strong>Obrigado!</strong> Iremos analisar o CEP <strong class="report_zipcode_callback"></strong> e futuramente vamos amar atender por ai :)
							</span>
							<br/><br/><button type="button" class="btn btn-success btn_modal_system">Fechar</button>
						</p>
					</div>
					<!--Box Success--->
					<div class="box_success_zipcode" style="display: none;">
						<?php if ( $settings['message_system'] == "yes" ) { ?>
							<p class="msg_success_zipcode">
								<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/icon_success.png" alt="icon error" class="icon_success_zipcode">
								<span>
									<strong>Parabéns!</strong> Estamos felizes que seu CEP está em nossa área de cobertura.
								</span>
							</p>
						<?php } ?>
						<div class="box_zipcode_callback"></div>
					</div>
					<!--Box Result--->
					<div class="box_result_zipcode" style="display: none;">
						<?php echo $settings; ?>
					</div>
				</div>
			</div>
			<style type="text/css">
				#form_zipcode { 
					display: block;
					max-width: 345px; 
					margin: 0 auto; 
					padding: 10px; 
					font-size: 14px; 
					text-align: center;
				}
				#form_zipcode .box_search {
					display: block;
					min-width: 220px;
					margin: 0 auto;
					padding: 0;
                    border-radius: 3px;
                    overflow: hidden;
				}
                #form_zipcode input.input_zipcode::placeholder {
                  color: #5a2f01;
                }
				#form_zipcode input.input_name,
				#form_zipcode input.input_email,
				#form_zipcode input.input_whatsapp {
				    width: 100%;
				    min-width: 220px;
				    margin-bottom: 15px;
				    border-radius: 3px;
				}
				#form_zipcode input.input_zipcode { 
					display: block;
					float: left;
					width: 100%;
					height: 35px;
					background-color: #ff820061;
					border-radius: 0px;
					border: 0px;
					padding: 8px 20px;
					margin: 0;
					color: #5a2f01;
				}
				#form_zipcode button.submit_zipcode { 
					display: block;
					width: 35px;
					float: right;
					height: 35px;
					background-color: #ff8200;
					border-radius: 0px; 
					border:0px;
					margin: 0px; 
				    padding: 8px 10px;
					color: #eaffff;
					z-index: 3;
					margin-top: -35px;
					text-align: right;
				}
				#form_zipcode input:focus,
				#form_zipcode button:focus {
					border: 0px !important;
					outline: 0px !important;
				}
				#form_zipcode .btn {
					border-radius: 5px;
					transition: none;
					width: 100%;
				}
				#form_zipcode .btn-success {
					color: #fff;
    				background-color: #28a745;
    				border-color: #28a745;
    				cursor: pointer;
    				height: auto !important;
				}
				#form_zipcode .btn-danger {
					color: #fff;
    				background-color: #dc3545;
    				border-color: #dc3545;
    				cursor: pointer;
    				height: auto !important;
				}
				#form_zipcode .msg_empty_name,
				#form_zipcode .msg_empty_email,
				#form_zipcode .msg_empty_whatsapp,
				#form_zipcode .msg_empty_zipcode { 
					display: block; 
					color: #cc0000; 
					margin-top: 10px;
				}
				#form_zipcode .msg_loading_zipcode { 
					display: block; 
					color: #0d8c71; 
					margin-top: 10px;
				}
				#form_zipcode .icon_loading_zipcode { 
					display: inline-block; 
					margin-bottom: -10px;
				}
				#form_zipcode .box_error_zipcode p,
				#form_zipcode .box_success_zipcode p,
				#form_zipcode .box_report_success_zipcode p { 
					margin: 0px;
				}
				#form_zipcode .box_error_zipcode a { 
					color: #cc0000;
					text-decoration: none;
				}
				#form_zipcode .box_error_zipcode a:hover { 
					color: #000;
				}
				#form_zipcode .box_error_zipcode, 
				#form_zipcode .box_success_zipcode,
				#form_zipcode .box_report_success_zipcode,
				#form_zipcode .box_result_zipcode { 
					display: block; 
					max-width: 345px;
					margin: 0 auto;
					margin-bottom: 20px;
					padding: 10px;
					text-align: center;
					background-color: #FFF;
					opacity: 1;
				}
				#form_zipcode .box_error_zipcode { 
					border: 5px solid #cc0000;
				}
				#form_zipcode .box_success_zipcode,
				#form_zipcode .box_report_success_zipcode,
				#form_zipcode .box_result_zipcode { 
					border: 5px solid #0d8c71;
				}
				#form_zipcode .box_error_zipcode img.icon_error_zipcode, 
				#form_zipcode .box_report_success_zipcode img.icon_success_zipcode,
				#form_zipcode .box_success_zipcode img.icon_success_zipcode { 
					display: block;
					width: 38px;
					margin: 0 auto;
					text-align: center;
				}
				#form_zipcode .box_success_zipcode img {
					width: 100%;
					height: auto;
					padding: 0px;
					margin: 0px;
				}
				#form_zipcode .btn_modal_system {
					width: 100%;
					height: 50px;
				}
				#form_zipcode .btn_modal_close {
					width: 38px;
					height: 38px;
					margin: 5px;
					float: right;
					cursor: pointer;
				}
				#form_zipcode .box_zipcode_callback {
					margin: 10px 0px;
					padding: 10px 0px 0px;
				}
				#form_zipcode .box_zipcode_callback h2 {
					color: #0d8c71;
					font-weight: bold;
				}
				<?php if ( $settings['modal_system'] == "yes" ) { ?>
				#form_zipcode .modal_system {
					display: block;
					position: fixed;
					margin: 0px;
					padding: 0px;
					background-color: #000;
					width: 100%;
					height: 100%;
					z-index: 9999;
					/*opacity: 0.9;*/
					top: 0;
					left: 0;
				}
				<?php } ?>
			</style>
			<script src="//code.jquery.com/jquery-3.5.1.js"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
			<script type="text/javascript">
			$(document).ready(function(){
				$( ".input_zipcode" ).mask("99.999-999");
				$( ".submit_zipcode" ).click(function() {
					$( ".empty_name" ).attr("style", "display:none;");
					$( ".empty_email" ).attr("style", "display:none;");
					$( ".empty_whatsapp" ).attr("style", "display:none;");
					$( ".box_error_zipcode" ).attr("style", "display:none;");
					$( ".box_success_zipcode" ).attr("style", "display:none;");
					var name        = $( ".input_name" ).val();
					var email       = $( ".input_email" ).val();
					var whatsapp    = $( ".input_whatsapp" ).val();
					var zipcode     = $( ".input_zipcode" ).val();
					if ( name == "" || name.length < 3 ) {
						$( ".input_name" ).focus();
						$( ".empty_name" ).attr("style", "display:block;");
					} else if ( email == "" || email.length < 10 ) {
						$( ".input_email" ).focus();
						$( ".empty_email" ).attr("style", "display:block;");
					} else if ( whatsapp == "" || whatsapp.length < 11 ) {
						$( ".input_whatsapp" ).focus();
						$( ".empty_whatsapp" ).attr("style", "display:block;");
					} else if ( zipcode == "" || zipcode.length < 10 ) {
						$( ".input_zipcode" ).focus();
						$( ".search_zipcode" ).attr("style", "display:none;");
						$( ".empty_zipcode" ).attr("style", "display:block;");
					} else {
						$( ".box_zipcode_callback" ).html( '' );
						$( ".empty_zipcode" ).attr("style", "display:none;");
						$( ".search_zipcode" ).attr("style", "display:block;");
						var form_data = new FormData();	
						form_data.append( 'action', 'search_your_zipcode_callback' );
						form_data.append( 'name', name );
						form_data.append( 'email', email );
						form_data.append( 'whatsapp', whatsapp );
						form_data.append( 'zipcode', zipcode );
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							contentType: false,
							processData: false,
							data: form_data,
							success:function( response ) {
								console.log( response );
								if( response == '' ) {
									$( ".search_zipcode" ).attr("style", "display:none;");
									$( ".modal_system" ).attr("style", "display:block;");
									$( ".box_error_zipcode" ).attr("style", "display:block;");
									$( ".btn-report-zipcode" ).attr( "id", zipcode );
								} else {
									$( ".search_zipcode" ).attr("style", "display:none;");
									$( ".modal_system" ).attr("style", "display:block;");
									$( ".box_success_zipcode" ).attr("style", "display:block;");
									$( ".box_zipcode_callback" ).html( response );
								}
							}
						});
					}
				});
				$( ".btn-report-zipcode" ).click(function() {
					var name        = $( ".input_name" ).val();
					var email       = $( ".input_email" ).val();
					var whatsapp    = $( ".input_whatsapp" ).val();
					//var zipcode     = $( ".input_zipcode" ).val();
					var zipcode     = $( ".btn-report-zipcode" ).attr("id");
					var form_data = new FormData();	
					form_data.append( 'action', 'report_search_your_zipcode_callback' );
					form_data.append( 'name', name );
					form_data.append( 'email', email );
					form_data.append( 'whatsapp', whatsapp );
					form_data.append( 'zipcode', zipcode );
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						contentType: false,
						processData: false,
						data: form_data,
						success:function( response ) {
							console.log( response );
							$( ".box_error_zipcode" ).attr("style", "display:none;");
							$( ".box_report_success_zipcode" ).attr("style", "display:block;");
							$( ".report_zipcode_callback" ).html( zipcode );
						}
					});
				});
				$( ".btn_modal_system" ).click(function() {
					$( ".search_zipcode" ).attr("style", "display:none;");
					$( ".empty_zipcode" ).attr("style", "display:none;");
					$( ".modal_system" ).attr("style", "display:none;");
					$( ".box_error_zipcode" ).attr("style", "display:none;");
					$( ".box_report_success_zipcode" ).attr("style", "display:none;");
				});
			});
			</script>
			<?php
		}
		//=>

		public function report_search_your_zipcode_callback() {
		    $name           = $_POST['name'];
		    $email          = $_POST['email'];
		    $whatsapp       = $_POST['whatsapp'];
			$zipcode        = $_POST['zipcode'];
			$admin_email    = get_option( 'admin_email' );
			
            ini_set( 'display_errors', 1 );
            error_reporting( E_ALL );
            
            $from       = $admin_email;
            $to         = $admin_email;
            $subject    = "Sugestão de CEP enviado pelo site";
            $message    = "Olá, adminsitrador. <br/><br/> Sugestão de CEP enviado pelo site: <br/> CEP: <strong>{$zipcode}</strong><br/><br/> Nome do Cliente: <strong>{$name}</strong><br/> E-mail: <strong>{$email}</strong><br/> WhatsApp: <strong>{$whatsapp}</strong>";
            $headers[]  = "MIME-Version: 1.0";
            $headers[]  = "Content-type: text/html; charset=UTF-8";
            $headers[]  = "From: {$admin_email}";
            $headers[]  = "X-Mailer: PHP/" . phpversion();
            
            if( mail( $to, $subject, $message, implode("\r\n", $headers) ) ) {
			    return 1; die();
            } else {
               return 0; die(); 
            }
		}
		//=>

		public function search_your_zipcode_callback() {
			global $wpdb;
			$result 	= '';
			$zipcode 	= $_POST['zipcode'];
			$zipcode 	= str_replace( '.', '', $zipcode );
			$zipcode 	= str_replace( '-', '', $zipcode );
			$settings 	= get_option( 'search_your_zipcode_settings' );
			$zc_posts 	= $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_status = 'publish' AND post_type = 'search_zipcode';" );

			if( isset( $zc_posts ) && ! empty( $zc_posts ) && count( $zc_posts ) >= 0 ) {
    			foreach ( $zc_posts as $key => $zc_post ) {

    			    $zipcode_settings 		= get_post_meta( $zc_post->ID, '_search_your_zipcode', true );
    			    $date_zipcode_settings 	= get_post_meta( $zc_post->ID, '_search_your_date_zipcode', true );

    			    if( isset( $zipcode_settings ) && ! empty( $zipcode_settings ) && count( $zipcode_settings ) >= 0 ) {
	    			    foreach ( $zipcode_settings as $key => $cep ) {
	    			        if( isset( $cep ) && $cep == $zipcode ) {
								if ( $settings['message_system'] == "yes" ) {
		            				$result 	.= $zc_post->post_content;
								}
	            				$thumbnail 	= wp_get_attachment_image_src( get_post_thumbnail_id( $zc_post->ID ), array( '300', '300' ), true );
	            				if ( isset( $thumbnail[0] ) && ! empty( $thumbnail[0] ) ) {
	            					$result .= '<img src="'. esc_url( $thumbnail[0] ) . '" class="thumbnail img" alt="thumbnail">';
	            				}
	            				$link_plan = get_post_meta( $zc_post->ID, '_search_your_zipcode_link_plan', true ); 
	            				if ( isset( $link_plan ) && ! empty( $link_plan ) ) {
	            					$result .= '<br/><a class="btn button button-secondary" href="' . $link_plan . '">Ver o Plano</a>';
	            				}
	            				if ( isset( $settings['text_custom'] ) && $settings['text_custom'] != "yes" ) {
	            					$result = '<span></span>';
	            				}
	            				if ( isset( $date_zipcode_settings ) && ! empty( $date_zipcode_settings ) ) {
	            					$result .= "<strong>Datas Disponíveis:</strong><br/>";
	            					foreach ( $date_zipcode_settings as $key => $date_zipcode ) {
	            						$br_date_zipcode = date( "d/m/Y", strtotime( $date_zipcode ) );
	            						$result 		.= "<span>" . $br_date_zipcode . "</span><br/>";
	            					}
	            				}
	            				echo $result;
	            				die();
	    			        }
    			    	}
    			    }
    			}
			}
			echo $result;
			die();
		}
		//=>

		public function wp_page_admin_settings_callback() {
		        global $wpdb;
		        if( isset( $_GET['_wpnonce'] ) && isset( $_GET['post_id'] )  ) {
		            $_wpnonce = sanitize_text_field( $_GET['_wpnonce'] );
		            if ( wp_verify_nonce( $_wpnonce, "remove-zipcode" ) ) {
		                $post_id = $_GET['post_id'];
		                $zipcode = $_GET['remove_zipcode'];
                        $zipcode_settings = get_post_meta( $post_id, '_search_your_zipcode', true );
                        if( ! empty( $zipcode ) && ! empty( $post_id ) ) {
                            foreach ( $zipcode_settings as $key => $cep ) {
                                if( $cep != $zipcode ) {
                                    $zipcodes[] = $cep;    
                                }
                            }
                            $wp_zipcode_settings = $zipcodes;
                            $update_post_meta = update_post_meta( $post_id, '_search_your_zipcode', $wp_zipcode_settings );
                            if( $update_post_meta ) {
                                $url_admin_edit_post = admin_url("post.php?post={$post_id}&action=edit");
                                wp_redirect( $url_admin_edit_post );
                                exit();
                            }
                        }
		            }

		            $_wpnonce = sanitize_text_field( $_GET['_wpnonce'] );
		            if ( wp_verify_nonce( $_wpnonce, "remove-date-zipcode" ) ) {
		                $post_id = $_GET['post_id'];
		                $date_zipcode = $_GET['remove_date_zipcode'];
                        $date_zipcode_settings = get_post_meta( $post_id, '_search_your_date_zipcode', true );
                        if( ! empty( $date_zipcode ) && ! empty( $post_id ) ) {
                            foreach ( $date_zipcode_settings as $key => $date ) {
                                if( $date != $date_zipcode ) {
                                    $date_zipcodes[] = $date;    
                                }
                            }
                            $wp_date_zipcode_settings = $date_zipcodes;
                            $update_post_meta = update_post_meta( $post_id, '_search_your_date_zipcode', $wp_date_zipcode_settings );
                            if( $update_post_meta ) {
                                $url_admin_edit_post = admin_url("post.php?post={$post_id}&action=edit");
                                wp_redirect( $url_admin_edit_post );
                                exit();
                            }
                        }
		            }
		        }
	
				if( isset( $_POST['_update'] ) && isset( $_POST['_wpnonce'] ) && isset( $_POST['_action'] ) ) {

					$message_error 	= "";
					$message 		= "error";
					$_update 		= sanitize_text_field( $_POST['_update'] );
					$_wpnonce 		= sanitize_text_field( $_POST['_wpnonce'] );
					$_action 		= sanitize_text_field( $_POST['_action'] );

					if( isset( $_wpnonce ) && isset( $_update ) ) {						
						if ( wp_verify_nonce( $_wpnonce, "update" ) && $_action == "update" ) {

							$new_settings['enabled'] = isset( $_POST['enabled'] ) ? $_POST['enabled'] : 'no';
							$new_settings['text_system'] = isset( $_POST['text_system'] ) ? $_POST['text_system'] : 'no';
							$new_settings['text_custom'] = isset( $_POST['text_custom'] ) ? $_POST['text_custom'] : 'no';
							$new_settings['modal_system'] = isset( $_POST['modal_system'] ) ? $_POST['modal_system'] : 'no';
							$new_settings['message_system'] = isset( $_POST['message_system'] ) ? $_POST['message_system'] : 'no';						
							$new_settings['url_contact'] = isset( $_POST['url_contact'] ) ? $_POST['url_contact'] : '#';
							update_option( 'search_your_zipcode_settings', $new_settings );

							$message = "updated";

						} else if ( wp_verify_nonce( $_wpnonce, "import" ) && $_action == "import" ) {

							$overwrite 		= sanitize_text_field( $_POST['overwrite'] );
							$post_id 		= sanitize_text_field( $_POST['post-id'] );
							$your_zipcodes 	= $_FILES['your-zipcodes'];

							if ( ! empty( $post_id ) && ! empty( $your_zipcodes ) ) {

								if ( $overwrite == "yes" ) {
									update_post_meta( $post_id, '_search_your_zipcode', "" );
								}

								if ( ! function_exists( 'wp_handle_upload' ) ) {
								    require_once( ABSPATH . 'wp-admin/includes/file.php' );
								}

								$file_name 		= $your_zipcodes['name'];
								$file_type 		= $your_zipcodes['type'];
								$file_tmp_name 	= $your_zipcodes['tmp_name'];
								$file_error 	= $your_zipcodes['error'];
								$file_size 		= $your_zipcodes['size'];

								if ( $file_size <= 40000 ) {

									$file_zipcodes = file( $file_tmp_name );
									asort( $file_zipcodes );

									foreach ( $file_zipcodes as $key => $zipcodes ) {
										$zipcode = substr( str_replace( '-', '', str_replace( '.', '', str_replace( ',', '', trim( $zipcodes ) ) ) ), 0, 8 );
										if( ! empty( $zipcode ) && strlen( $zipcode ) == 8 ) {
											$zipcode_settings = get_post_meta( $post_id, '_search_your_zipcode', true );
											asort( $zipcode_settings );

							    			if( empty( $zipcode_settings ) ) {
							    			    $zipcode_settings = array(
							    			        $zipcode,
							    			    );
							    			    update_post_meta( $post_id, '_search_your_zipcode', $zipcode_settings );
							    			} else {
							    			    array_push( $zipcode_settings, $zipcode );
							    			    update_post_meta( $post_id, '_search_your_zipcode', $zipcode_settings );
							    			}
									    }
									}

									$message = "updated";
								} else {
									$message_error = "size";
								}
							}
						}
					}
				}
				$settings = array();
				$settings = get_option( 'search_your_zipcode_settings' );
		        ?>
				<div id="wpwrap">
				    <h1>Painel CEPs Disponíveis</h1>
				    <p>Abaixo você pode configurar o plugin preenchendo os dados gerais.<p/>	

				    <?php if( isset( $message ) ) { ?>
				        <div class="wrap">
				    		<?php if( $message == "updated" ) { ?>
				            <div id="message" class="updated notice is-dismissible" style="margin-left: 0px;">
				                <p>Sucesso! Os dados foram atualizações com sucesso!</p>
				                <button type="button" class="notice-dismiss">
				                    <span class="screen-reader-text">
				                        Dispensar este aviso.
				                    </span>
				                </button>
				            </div>
				            <?php } ?>

				            <?php if( $message == "error" ) { ?>
				            <div id="message" class="updated error is-dismissible" style="margin-left: 0px;">
				                <p>Erro! Não conseguimos fazer as atualizações!</p>
				                <button type="button" class="notice-dismiss">
				                    <span class="screen-reader-text">
				                        Dispensar este aviso.
				                    </span>
				                </button>
				            </div>
				        	<?php } ?>

				            <?php if( $message_error  != "" ) { ?>
				            <div id="message" class="updated error is-dismissible" style="margin-left: 0px;">
				            	<?php if( $message_error  == "size" ) { ?>
				                <p>Arquivo muito grande! Limite máximo de 40KB ou 4mil CEPs.</p>
				                <?php } ?>
				                <button type="button" class="notice-dismiss">
				                    <span class="screen-reader-text">
				                        Dispensar este aviso.
				                    </span>
				                </button>
				            </div>
				        	<?php } ?>

				    	</div>
				    <?php } ?>
				    
				    <div class="wrap ">

			            <?php
							if( isset( $_GET['tab'] ) ) {
								$tab = esc_attr( $_GET['tab'] );
							}
						?>

			            <nav class="nav-tab-wrapper wc-nav-tab-wrapper">
			           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=search_zipcode' ) ); ?>" class="nav-tab <?php if( $tab == "" ) { echo "nav-tab-active"; }; ?>">
								Configurações
			                </a>
			           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=search_zipcode&tab=import' ) ); ?>" class="nav-tab <?php if( $tab == "import" ) { echo "nav-tab-active"; }; ?>">
								Importar CEPs
			                </a>
			            </nav>

			            <?php if( ! isset( $tab ) ) { ?>

			            <!--form-->
			        	<form method="POST" id="mainform" name="mainform" enctype="multipart/form-data">
			                <!---->
			                <table class="form-table">
			                    <tbody>
			                        <!---->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Habilitar
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="enabled" value="yes" <?php if( $settings['enabled'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Texto do topo<br/>
			                                </label>
			                                <i style="font-size: 12px; font-weight: normal;">
												Texto pardão acima do campo de busca do sistema.
		                                    </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="text_system" value="yes" <?php if( $settings['text_system'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Texto Personalizado<br/>
			                                </label>
			                                <i style="font-size: 12px; font-weight: normal;">
												Texto personalizado é aquele que vc inseriu no posttype.
		                                    </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="text_custom" value="yes" <?php if( $settings['text_custom'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Modal no resultado<br/>
			                                </label>
			                                <i style="font-size: 12px; font-weight: normal;">
												Efeito pardão de sucesso na entrega do resultado.
		                                    </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="modal_system" value="yes" <?php if( $settings['modal_system'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Mensagem do Sistema<br/>
			                                </label>
			                                <i style="font-size: 12px; font-weight: normal;">
												Mensagem pardão de sucesso do sistema.
		                                    </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="message_system" value="yes" <?php if( $settings['message_system'] == "yes" ) { echo 'checked="checked"'; } ?>>
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Link de Contato<br/>
			                                </label>
			                                <i style="font-size: 12px; font-weight: normal;">
												Para obter mais informações de futuro atendimento para CEP dele.
		                                    </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="text" name="url_contact" value="<?php echo $settings['url_contact'];?>" style="width: 100%;"  placeholder="http:// ou https://">
			                                </label>
			                            </td>
			                        </tr>
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Shortcode<br/>
			                                    <i style="font-size: 12px; font-weight: normal;">
													Exibir o campo de pesquisa por CEPs no site.
			                                    </i>
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    [search_your_zipcode]
			                                </label>
			                            </td>
			                        </tr>
			                        <!----->
			                   </tbody>
			                </table>
			                <!---->
			                <hr/>
			                <div class="submit">
			                    <button class="button-primary" type="submit">Salvar Alterações</button>
			                    <input type="hidden" name="_update" value="yes">
			                    <input type="hidden" name="_action" value="update">
			                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'update' ) ); ?>">
			                </div>
			                <!---->  
			            </form>
			        	<!----> 
			        	<?php } else if ( $tab == "import") { ?>

			            <!--form-->
			        	<form method="POST" id="mainform" name="mainform" enctype="multipart/form-data">
			                <!---->
			                <table class="form-table">
			                    <tbody>
			                        <!---->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Sobrescrever
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="checkbox" name="overwrite" value="yes" checked="checked">
			                                    <span>Sim</span>
			                                </label>
			                           </td>
			                        </tr>  
			                        <!----->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    ID Post:
			                                </label>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="text" name="post-id" value="" required="">
			                                </label>
			                           </td>
			                        </tr>  
			                        <!---->
			                        <tr valign="top">
			                            <th scope="row">
			                                <label>
			                                    Arquivo (\n)
			                                </label>
			                                <br/><hr/>
			                                <i>
			                                	<strong>Obs:</strong> Os CEPs precisam está um abaixo do outro.
			                                </i>
			                            </th>
			                            <td>
			                                <label>
			                                    <input type="file" name="your-zipcodes" required="">
			                                </label>
			                                <br/><hr/>
			                                <i>
			                                	<strong>Obs:</strong> Limite máximo de 40KB ou 4mil CEPs.
			                                </i>
			                           </td>
			                        </tr>  
			                        <!----->
			                   </tbody>
			                </table>
			                <!---->
			                <hr/>
			                <div class="submit">
			                    <button class="button-primary" type="submit">Importar Agora</button>
			                    <input type="hidden" name="_update" value="yes">
			                    <input type="hidden" name="_action" value="import">
			                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'import' ) ); ?>">
			                </div>
			                <!---->  
			            </form>
			        	<!----> 

			        	<?php } ?>   
				     </div>    
				</div>
			<?php
		}
		//=>
	}
	//..
	new Search_Your_ZipCode();
}