<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzon\Administrator\View\Dashboard;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Renders the WishBox Ozon administrator dashboard.
 *
 * @since 1.0.0
 */
final class HtmlView extends BaseHtmlView
{
	/**
	 * Whether the current user can edit component options.
	 *
	 * @var bool
	 *
	 * @since 1.0.0
	 */
	public bool $canConfigure = false;

	/**
	 * Display the dashboard.
	 *
	 * @param   string|null  $tpl  Template name.
	 *
	 * @return void
	 *
	 * @throws Exception When the application cannot be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function display($tpl = null): void
	{
		$identity = Factory::getApplication()->getIdentity();

		$this->canConfigure = $identity->authorise('core.admin', 'com_wishboxozon')
			|| $identity->authorise('core.options', 'com_wishboxozon');

		ToolbarHelper::title(Text::_('COM_WISHBOXOZON_DASHBOARD_TITLE'), 'cog');

		if ($this->canConfigure)
		{
			ToolbarHelper::preferences('com_wishboxozon');
		}

		parent::display($tpl);
	}
}
