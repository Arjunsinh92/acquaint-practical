<?php
/**
 * Template Name: Products
 */
get_header(); ?>
<div class="container">
	<div class="row">
	<select class="form-select" id="products-opt">
		<option value="all">Default</option>
		<option value="total_sales">Show only popular products</option>
		<option value="featured">Show only featured products</option>
	</select>
	<input type="hidden" name="action" value="myfilter">
	</div>
	<div class="products">
	<div class="loader-img" id="post_rslt" style="display: none";><img src="<?php echo get_template_directory_uri(); ?>/assets/images/ajax-loader.svg"> </div>
		<?php 
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'hide_empty' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
			//'tax_query' => $tax_query
		); 
		$qry = new WP_Query($args); ?>
		<ul class="products row columns-4 my-3 ajax-result">
		
			<?php if($qry->have_posts()){
				while($qry->have_posts()){ $qry->the_post(); ?>
					<li class="col-md-4 my-3 entry product">
						<a href="<?php echo get_the_permalink(); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
							<?php if(has_post_thumbnail()){
								echo get_the_post_thumbnail(get_the_ID());
							} ?>
							<h3 class="woocommerce-loop-product__title"><?php echo get_the_title(); ?></h3>
							<span class="price"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span><?php echo get_the_ID(); ?></bdi></span></span>
						</a>
						<a href="<?php echo get_the_permalink(); ?>" class="button wp-element-button add_to_cart_button" data-product_id="<?php echo get_the_ID(); ?>" rel="nofollow">Buy Now</a>
					</li>
				<?php } 
			} ?>
		</ul>
	</div>
</div>
<?php get_footer();