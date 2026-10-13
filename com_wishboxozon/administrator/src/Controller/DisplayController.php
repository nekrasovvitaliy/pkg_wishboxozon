<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzon\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Displays the WishBox Ozon administrator dashboard.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class DisplayController extends BaseController
{
	/**
	 * Default component view.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	protected $default_view = 'dashboard';

	/**
	 * Display the requested administrator view.
	 *
	 * @param   bool   $cachable   Response cache flag.
	 * @param   array  $urlparams  Safe URL parameter filters.
	 *
	 * @return static
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function display($cachable = false, $urlparams = [])
	{
		return parent::display($cachable, $urlparams);
	}
}
