<?php
$page = ($_GET['page'] != null && (int) $_GET['page'] > 0) ? (int) $_GET['page'] : 1;
$domain_url = \helper\options::options_by_key_type('base_url');
$theme_url = '/' . get_config('root_theme') . "/" . \helper\options::options_by_key_type('index_theme');
$category_obj = \helper\category::find_category_by_slug($slug, 'product');
$fiterrequest =  $_REQUEST['filter'];
?>

<style>
.category_child{display:none;}
.category_child_<?=$fiterrequest?>{display:block;}
</style>
<?php
if (!$category_obj) {
    load_response()->redirect('/');
}

$metdata = json_decode($category_obj->metadata);

$thumb = $domain_url . \helper\options::options_by_key_type('logo');
$url = $domain_url . '/' . $category_obj->slug;
$meta_title = ($metdata->title) ? $metdata->title : $category_obj->name;
$meta_description = $metdata->description;
$meta_keyword = ($metdata->keywords) ? $metdata->keywords : strtolower($category_obj->name);
$faceseodata = array(
    'title' => $meta_title,
    'description' => $meta_description,
    'keywords' => $meta_keyword,
    'titlefacebook' => $meta_title,
    'thumbfacebook' => $thumb,
    'urlfacebook' => $url,
    'desfacebook' => $meta_description
); 

$sort = $_GET['sort'] ? $_GET['sort'] : '';
$custom = \helper\themes::get_layout('metadata', $faceseodata);

echo \helper\themes::get_layout('header', array('custom' => $custom));
?> 
<div id="main-primary">
    <div class="container">
        <div class="row">
            <div id="slider-danh-muc" class="owl-carousel owl-theme owl-loaded owl-drag">
                <div class="owl-stage-outer"><div class="owl-stage"></div></div><div class="owl-nav disabled"><div class="owl-prev">prev</div><div class="owl-next">next</div></div><div class="owl-dots disabled"></div></div>
        </div>
    </div>
    <div class="container">
        <div class="row ex_lproducts">
            <div id="primary" class="content-area ">
                <main id="main" class="site-main site-main-nmt" role="main">
                    <div id="slider_danh_muc" class="owl-carousel owl-theme">
                    </div>
                    <header class="woocommerce-products-header">
                    </header>
                    <input type="hidden" name="filter_terms" id="filter_terms"/>
                    <input type="hidden" name="sort" id="sort_by"/>
                    <?php
                    $menu_category_data = \helper\menu::find_menu_by_menugroup('menu_category');
                    $menu_category = \helper\menu::to_menu_directory_style($menu_category_data);
                    ?> 
                    <div class="ex_dmc">
                        <div class="menu-menu-dienthoai-container">
                            <ul id="menu-menu-dienthoai" class="menu">
                                <?php foreach ($menu_category as $m_category): ?>
                                    <li class="menu-item menu-item-type-taxonomy menu-item-object-product_cat current-menu-item">
                                        <a href="<?php echo $m_category->url; ?>" aria-current="page">
                                            <?php echo $m_category->title; ?>    
                                        </a>
                                    </li>
                                <?php endforeach; ?> 
                            </ul>
                        </div>   
                    </div>
                    <div class="ex_locsp">
						<h1 class="woocommerce-products-header__title page-title"><?php echo $category_obj->name; ?> <span style="text-transform: uppercase;"><?php if(isset($_REQUEST['filter']) && $_REQUEST['filter']){ echo $_REQUEST['filter'];}  ?></span></h1>
		
                        <ul id="filter_product" class="ex_filter ex_sapxep">
							<li class="filter-item filter-all">
                                <div class="filter-item__title filter-item__title_show-total">
                                    <div class="arrow-filter"></div>
                                    <svg width="12" height="14" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-cac55858=""><path d="M4.14742 13.4424C4.53671 13.6822 5.02227 13.7045 5.43217 13.4991L7.19217 12.6191C7.63818 12.3957 7.92007 11.9394 7.92007 11.4401V7.36743L11.0524 4.23243C11.3008 3.98578 11.44 3.64978 11.44 3.3V1.32C11.44 0.59125 10.8488 0 10.12 0H1.32C0.59125 0 0 0.59125 0 1.32V3.3C0 3.64976 0.139219 3.98578 0.387574 4.23243L3.51993 7.36743V12.3201C3.52079 12.7772 3.75811 13.2018 4.14742 13.4424ZM1.31998 3.29998V1.31998H10.12V3.29998L6.59998 6.81998V11.44L4.83998 12.32V6.81998L1.31998 3.29998Z" fill="#333333" data-v-cac55858=""></path></svg>
                                     <span>Bộ lọc</span>
                                </div>
								<div class="overlay" style="display: none;"></div>
                                <div class="filter-option filter-total show-total" style="display: none;">
									<div class="show-total-main">
										<button type="button" class="ex_close btn">X</button>
										<div class="choosedfilter" id="choosedfilter"></div>
										<?php
										 $filter = \helper\category::get_filter_group_show_total_nmt();
										 echo $filter; 
										?>
									</div>
                                </div>
                            </li>
							
						
                            <?php
                            $filter = \helper\category::get_filter_group_nmt();
                            echo $filter;
                            ?>
                            <li class="filter-item">
                                <div class="arrow-filter"></div>
                                <div class="filter-item__title"><div class="arrow-filter"></div> <span>Sắp xếp</span></div>
                                <div class="filter-option filter-option-nmt" style="display: none;"><div><div class="filter-childs ">
                                    <a class="sort-by" data-sort="popularity" href="/<?php echo $category_obj->slug; ?>?sort=popularity">Sản phẩm xem nhiều</a>
                                    <a class="sort-by" data-sort="price" href="/<?php echo $category_obj->slug; ?>?sort=price">Giá thấp đến cao</a>
                                    <a class="sort-by" data-sort="price-desc" href="/<?php echo $category_obj->slug; ?>?sort=price-desc">Giá cao đến thấp</a>
                                    </div> </div>
									<div class="choosedfilter" id="choosedfilter"></div>
                                </div>
                                
                            </li>
                        </ul>
                    </div>
                    <!-- <div class="choosedfilter" id="choosedfilter">

                    </div> end choosedfilter -->
					<div class="products-header-listcat">
						<?php
						$categories = \helper\category::get_categories_with_image();
						if (!empty($categories)) {
							echo '<div class="lst-quicklink ">';
							foreach ($categories as $category) {
								if($category->ancestry == '43'){
									// Lấy các giá trị cần thiết từ danh mục
									$id = $category->id;
									$name = $category->name;
									$slug = $category->slug;
									$image = $category->image; // Đảm bảo trường 'image' chứa đường dẫn tới ảnh
									// Tạo thẻ <a> và <img>
									echo '<a class="box-quicklink__item" href="?filter='. $slug . '" title="' . $name . '">';
									echo '<i class="quick-link-icon"></i>';
									echo '<img src="' . $image . '" alt="' . $name . '" />';
									echo '</a>';
								}
							}
							echo '</div>';
							echo '<div class="lst-quicklink_1">';
							foreach ($categories as $category) {
								if($category->ancestry == '43'){
									if($category->child ){
										$desired_category_ids = $category->child;
										$desired_category_slug = $category->slug;
										$desired_category_ids_array = explode(',', $desired_category_ids);
											if (isset($desired_category_ids_array) && !empty($desired_category_ids_array) && is_array($desired_category_ids_array)) {
												$category_ids = $desired_category_ids_array;
												echo '<div class="category_child category_child_'.$category->slug.'">';
												foreach ($category_ids as $id) {
													if (isset($id)) {
														$categories_with_links = \helper\category::find_category($id);
														$id = $categories_with_links->id;
														$name = $categories_with_links->name;
														$slug = $categories_with_links->slug;
														$product_category_limit = \helper\options::options_by_key_type('product_category_limit', 'display');
														echo '<a href="#" class="load-data-link box-name" data-page="'.$page.'" data-limit="'.$product_category_limit.'" data-category="'.$desired_category_slug.'" data-filter="'.$category_obj->slug.','.$desired_category_slug.','.$slug.'" data-sort="'.$sort.'"><span class="text">'.$name.'</span></a>';
													}
												}
												echo '</div>';
											}
										}
								}
							}
							echo '</div>';
						} else {
							echo "Không có danh mục nào có image.";
						}
						?>
					</div>
                   
				   <div class="woocommerce-notices-wrapper"></div>
                    <div id="product_container">
                        <?php
                        $product_category_limit = \helper\options::options_by_key_type('product_category_limit', 'display');
                        echo \helper\themes::get_layout('product_category', array('slug' => $category_obj->slug, 'limit' => $product_category_limit, 'filter' => $filter, 'sort' => $sort));
                        ?>	
                    </div>
                    <div class="loadingpost"></div>
                </main></div><div class="ex_lqnews">
            </div>
        </div>
    </div>
</div>
<?php 
$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
?>
<script>
$(document).ready(function() {
$('a.load-data-link').on('click', function (event) {
	event.preventDefault(); 
	$('a.load-data-link').removeClass('action'); 
	$(this).addClass('action');
	var page = $(this).data('page') || 1;
	var limit = $(this).data('limit') || 10;
	var category = $(this).data('category') || '';
	var filter = $(this).data('filter') || ''; 
	var sort = $(this).data('sort') || ''; 
	load_data(page, limit, category, filter, sort, '#product_container');
});
var filter = "<?php echo $filter; ?>";
if (filter) {
	var value = {
		id: filter,
		name: "Filter Name" // Bạn có thể thay thế bằng tên thực tế của filter
	};
	var filter_text = '<a class="remove-filter" data-id="' + filter + '"> <h2>' + filter + '</h2></a>';
	$(".choosedfilter").html(filter_text);
}
});

    window.addEventListener("DOMContentLoaded", function () {
        filter_product();

    });
    function filter_product() {
		$(".filter").click(function (e) {
            if ((e.target).tagName == 'INPUT')
                return true;
            e.preventDefault();
            var id = $(this).data('id');
			var hasShowTotal = id.indexOf('_show_total') !== -1;
		
			id = id.replace('_show_total', '');
			$('#' + id).click();
			$('#' + id + '_show_total').click();
			// $('#' + id + '_show_total').click();
			
        });
        $('.filter_box').change(function () {
            var filter;
            var filter_terms = '';
            var filter_text = '';
			var filter_terms = '<?php echo $filter; ?>';
			if (filter_terms) {
				var filter_text = '<a class="remove-filter" data-id="' + filter_terms + '" > <h2>' + filter_terms + '</h2></a>';
			}
            filter = checkboxValue();
			if(filter==''){
				$('.btn.filter').removeClass('active');
			}
            if(filter){					
                $.each(filter, function (index, value){
				
                    if (index != 0 || filter_terms != '') {
                        filter_terms += ","
                    }
					$('.btn.filter').removeClass('active');
					$.each(filter, function (index, value) {
						if (typeof value.id !== 'undefined' && value.id != 0) {
							$('.btn.filter.' + value.id).addClass('active');
						}
					});
					
                     filter_terms += value.id ;
                    filter_text += '<a class="remove-filter" data-id="' + value.id + '" href="/' + value.id + '"> <h2>' + value.name + '</h2> <i class="fa fa-times"></i> </a>';
                });
                filter_text += ' <a class="reset" href="/<?php echo $category_obj->slug; ?>">Xóa tất cả<i class="fa fa-times"></i></a>';
            }
            $(".choosedfilter").html(filter_text);
            $("#filter_terms").val(filter_terms);
            remove_filter();
            load_filter_product();
            //

            if ($('.remove-filter').length == 0) {
                $(".choosedfilter").html("");
            }

        });
        $('.sort-by').click(function (event) {
            event.preventDefault();
            var val = $(this).data('sort');
            $("#sort_by").val(val);
            load_filter_product();
        });


    }
    function load_filter_product() {
		
        var filter_terms = $("#filter_terms").val();
		
        var sort = $("#sort_by").val();
        if ($('#load_more').length) {
            $("#mansory-media").infiniteScroll('destroy');
        }
        load_data(<?php echo $page ?>,<?php echo $product_category_limit ?>, '<?php echo $category_obj->slug; ?>', filter_terms, sort, '#product_container');

    }

    function remove_filter() {
        $('.remove-filter').click(function (event) {
            event.preventDefault();
            var val = $(this).data('id');
            $("input[value=" + val + "][type=checkbox]").click();
			$('.btn.filter').removeClass('active');

			// $("input[value=" + val + "'_show_total'][type=checkbox]").click();
            if ($('.remove-filter').length == 0) {
                $("#choosedfilter").html("");
            }
        });
    }

    function checkboxValue()
    {
        var value = [];
		// $("#filter_product .filter-option").show();  // Hiển thị tất cả các phần tử .filter-option

        $("#filter_product").find('input[class=filter_box][type=checkbox]:checked').each(function (i) {

            var id = $(this).val();
            var name = $(this).data('name');
            value.push({'id': id, 'name': name});
		
        });
		
        return value;
    }

    //
    function load_data(page, limit, category, filter, sort, main_contain_id) { 
        jQuery(".loadingpost").show();
        var metadataload = {};
        metadataload.page = page;
        metadataload.limit = limit;
        metadataload.category = category;
        metadataload.filter = filter;
        metadataload.sort = sort;
        jQuery.ajax({
            url: "<?php echo get_format_uri('ajax', 'product-category') ?>",
            data: metadataload,
            type: 'GET',
            success: function (data) {
                jQuery(main_contain_id).html(data);
                jQuery(".loadingpost").hide();
                lazyload();
                //
                if ($('#load_more').length) {
                    var $container = $('#mansory-media').infiniteScroll({
                        // options
                        path: '#load_more',
                        append: '.mansory-item',
                        history: 'push'

                    });
                    $container.on('append.infiniteScroll', function (event, response, path, items) {
                        lazyload();
                    });
                }



            }
        });
    }
	
$(document).click(function(e) {
    var container = $('#filter_product');
    // Kiểm tra nếu mục tiêu của sự kiện không phải là container và cũng không phải là một phần tử con của container
    if (!container.is(e.target) && container.has(e.target).length === 0) {
		$('.filter-option').hide(); 
		$('.overlay').hide(); 
		$('.filter-item__title').removeClass("active");
    }
});


</script>
<?php echo \helper\themes::get_footer(); ?>