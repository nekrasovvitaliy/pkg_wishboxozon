<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

?>
<div class="container-fluid">
	<div class="card">
		<div class="card-body">
			<h2 class="card-title"><?php echo Text::_('COM_WISHBOXOZON_DASHBOARD_TITLE'); ?></h2>
			<p class="card-text"><?php echo Text::_('COM_WISHBOXOZON_DASHBOARD_DESCRIPTION'); ?></p>
			<?php if ($this->canConfigure) : ?>
				<a
					class="btn btn-primary"
					href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_wishboxozon'); ?>"
				>
					<?php echo Text::_('JTOOLBAR_OPTIONS'); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
