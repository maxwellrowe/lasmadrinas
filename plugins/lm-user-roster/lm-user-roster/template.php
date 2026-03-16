<?php
/**
 * Template for roster output.
 * Theme override path: yourtheme/lm-user-roster/template.php
 */

if ( ! defined('ABSPATH') ) exit;

?>

<?php $used_letters = []; ?>
<div class="<?php echo esc_attr($atts['class']); ?>">
	<div class="row">
		<div class="small-12 medium-8 columns">
			
			<a href="https://lasmadrinas.org/download/1203/" class="button hollow small"><strong>Download Roster</strong></a> &nbsp;&nbsp; <a href="https://lasmadrinas.org/member/roster/submit-changes-to-your-roster-listing/" class="button small">Submit an Edit</a>
			
		<!--/small-12--></div>
		
		<div class="small-12 medium-4 columns">
			
			<input id="search-roster" class="search-roster" name="search-roster" type="text" placeholder="Search roster..." />
			
		<!--/small-12--></div>
		
	<!--/row--></div>
	
	<div class="row">
		<div class="columns small-12 text-center" style="padding-top: 10px; padding-bottom: 10px;">
			<?php
			// Build a set of letters that actually exist in this roster
			$available_letters = [];
			
			foreach ($users as $u) {
				$ln = LM_User_Roster_Plugin::acf_user_field('contact_last_name', $u->ID);
				if (!$ln) continue;
			
				$letter = strtoupper(substr($ln, 0, 1));
			
				// only keep A–Z
				if ($letter >= 'A' && $letter <= 'Z') {
					$available_letters[$letter] = true;
				}
			}
			
			$available_letters = array_keys($available_letters);
			sort($available_letters);
			?>
			<?php foreach ($available_letters as $item): ?>
				<a href="#lname-<?php echo esc_html($item); ?>"
				   data-letter="lname-<?php echo esc_attr($item); ?>"
				   class="a-z-item"
				   style="padding: 0 6px;">
					<?php echo esc_html($item); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="lm-user-roster__grid">
		<?php foreach ($users as $user): ?>
			<?php
			$user_id = $user->ID;
			
			$acf_value = LM_User_Roster_Plugin::acf_user_field('member_status', $user_id);
			
			if (empty($acf_value)) {
				continue; // skip this user
			}

			// Profile picture (ACF image field name can be passed via shortcode attr profile_field="")
			$profile_img_url = LM_User_Roster_Plugin::profile_picture_url($user, $atts['profile_field']);

			/**
			 * ACF user fields from your export (field "name" values)
			 * Source: ACF JSON export  [oai_citation:1‡acf-export-2026-01-23.json](sediment://file_00000000b66071fda27462b0f1d76e73)
			 */
			$member_status        = LM_User_Roster_Plugin::acf_user_field('member_status', $user_id);
			$contact_last_name    = LM_User_Roster_Plugin::acf_user_field('contact_last_name', $user_id);
			$contact_last_name_first_character = strtoupper(substr($contact_last_name, 0, 1));
			$her_formal_name      = LM_User_Roster_Plugin::acf_user_field('her_formal_name', $user_id);
			$his_informal_title   = LM_User_Roster_Plugin::acf_user_field('his_informal_title', $user_id);
			$her_first            = LM_User_Roster_Plugin::acf_user_field('her_first', $user_id);
			$her_maiden           = LM_User_Roster_Plugin::acf_user_field('her_maiden', $user_id);
			$informal_dear        = LM_User_Roster_Plugin::acf_user_field('informal_dear', $user_id);

			$mailing_address_line = LM_User_Roster_Plugin::acf_user_field('mailing_address_line', $user_id);
			$mailing_city         = LM_User_Roster_Plugin::acf_user_field('mailing_city', $user_id);
			$mailing_state        = LM_User_Roster_Plugin::acf_user_field('mailing_state', $user_id);
			$mailing_zip_code     = LM_User_Roster_Plugin::acf_user_field('mailing_zip_code', $user_id);
			$mailing_country      = LM_User_Roster_Plugin::acf_user_field('mailing_country', $user_id);

			$date_of_birth        = LM_User_Roster_Plugin::acf_user_field('date_of_birth', $user_id);
			$year_joined          = LM_User_Roster_Plugin::acf_user_field('year_joined', $user_id);

			$home_phone           = LM_User_Roster_Plugin::acf_user_field('home_phone', $user_id);
			$her_cell_phone       = LM_User_Roster_Plugin::acf_user_field('her_cell_phone', $user_id);
			$her_work_phone       = LM_User_Roster_Plugin::acf_user_field('her_work_phone', $user_id);
			$fax                  = LM_User_Roster_Plugin::acf_user_field('fax', $user_id);
			$other_phone          = LM_User_Roster_Plugin::acf_user_field('other_phone', $user_id);

			// NOTE: field name is literally "e-mail" in your export  [oai_citation:2‡acf-export-2026-01-23.json](sediment://file_00000000b66071fda27462b0f1d76e73)
			// That's valid to fetch via get_field('e-mail', 'user_123'), but NOT valid as a PHP variable name,
			// so we store it in $email_general.
			$email_general        = LM_User_Roster_Plugin::acf_user_field('e-mail', $user_id);
			$her_email            = LM_User_Roster_Plugin::acf_user_field('her_email', $user_id);

			// Example display name logic (use whatever you want)
			$display_name = $user->display_name;
			if (!empty($her_formal_name)) {
				$display_name = $her_formal_name;
			} elseif (!empty($her_first) && !empty($contact_last_name)) {
				$display_name = trim($her_first . ' ' . $contact_last_name);
			}
			
			$letter = $contact_last_name_first_character;
			$id_attr = '';
			
			if ($letter && !isset($used_letters[$letter])) {
				$id_attr = 'id="lname-' . esc_attr($letter) . '"';
				$used_letters[$letter] = true;
			}
			?>

			<article <?php echo $id_attr; ?> class="lm-user-roster__card">
				<?php if ($profile_img_url): ?>
					<div class="lm-user-roster__avatar">
						<img src="<?php echo esc_url($profile_img_url); ?>" alt="<?php echo esc_attr($display_name); ?>" loading="lazy" />
					</div>
				<?php endif; ?>

				<div class="lm-user-roster__body">
					<div class="lm-user-roster__body__start">
						<h3>
							<?php if($contact_last_name) {
								echo $contact_last_name . ',';
							} ?>
							<?php if($her_first) {
								echo ' ' . $her_first;
							} ?>
							<?php if($her_maiden) {
								echo ' ' . $her_maiden;
							} ?>
						</h3>
						<ul>
							<li>
								<?php if($her_formal_name) {
									echo $her_formal_name;	
								} ?>
								<?php if($his_informal_title) {
									echo ' (' . $his_informal_title . ')';
								} ?>
							</li>
							<?php if($informal_dear) {
								echo '<li>&quot;' . $informal_dear . '&quot;</li>';
							} ?>
						</ul>
						
						<ul class="lm-user-roster__meta">
							<?php if($date_of_birth) { ?>
								<?php 
									$date_obj = DateTime::createFromFormat('d-M', $date_of_birth);
									if ($date_obj) {
										$formatted_dob = $date_obj->format('n/j'); // 9/30
									}
								?>
								<?php if (!empty($formatted_dob)) { ?>
									<li>
										<span class="fa fa-birthday-cake"></span>
										<span>Birthday: <strong><?php echo esc_html($formatted_dob); ?></strong></span>
									</li>
								<?php } ?>
							<?php } ?>
							<?php if($member_status) { ?>
								<li>
									<span class="fa fa-crown"></span>
									<span>Status: <strong><?php echo $member_status; ?></strong></span>
								</li>
							<?php } ?>
							<?php if($year_joined) { ?>
								<li>
									<span class="fa fa-check-circle"></span>
									<span>Joined: <strong><?php echo $year_joined; ?></strong></span>
								</li>
							<?php } ?>
						</ul>
					</div>
					<div class="lm-user-roster__body__end">
						<ul>
							<?php if($home_phone) { ?>
								<li>H: <a href="tel:<?php echo $home_phone; ?>"><?php echo $home_phone; ?></a></li>
							<?php } ?>
							<?php if($her_cell_phone) { ?>
								<li>C: <a href="tel:<?php echo $her_cell_phone; ?>"><?php echo $her_cell_phone; ?></a></li>
							<?php } ?>
							<?php if($her_work_phone) { ?>
								<li>W: <a href="tel:<?php echo $her_work_phone; ?>"><?php echo $her_work_phone; ?></a></li>
							<?php } ?>
							<?php if($fax) { ?>
								<li>F: <?php echo $fax; ?></li>
							<?php } ?>
						</ul>
						
						<ul>
							<?php if($mailing_address_line) { ?>
								<li>
									<?php echo $mailing_address_line; ?>
									<br />
									<?php echo $mailing_city; ?>, <?php echo $mailing_state; ?> <?php echo $mailing_zip_code; ?>
								</li>
								<li><a href="https://www.google.com/maps/search/?api=1&query=<?php echo $mailing_address_line; ?> <?php echo $mailing_city; ?> <?php echo $mailing_state; ?> <?php echo $mailing_zip_code; ?>" target="_blank">Directions <span class="fa  fa-external-link" style="font-size: .6rem;"></span></a></li>
							<?php } ?>
							
						</ul>
					</div>
				</div>
			</article>

		<?php endforeach; ?>
	</div>
</div>