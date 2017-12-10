<?php
declare(strict_types=1);

namespace SRAG\Learnplaces\container\provider;

use ilLearnplacesPlugin;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SRAG\Learnplaces\gui\block\RenderableBlockViewFactory;
use SRAG\Learnplaces\gui\block\RenderableBlockViewFactoryImpl;
use SRAG\Learnplaces\service\media\PictureService;
use SRAG\Learnplaces\service\publicapi\block\ConfigurationService;
use SRAG\Learnplaces\service\publicapi\block\LearnplaceService;
use SRAG\Learnplaces\service\publicapi\block\PictureBlockService;
use SRAG\Learnplaces\service\publicapi\block\PictureUploadBlockService;
use SRAG\Learnplaces\service\publicapi\block\RichTextBlockService;
use xsrlContentGUI;
use xsrlPictureBlockGUI;
use xsrlPictureUploadBlockGUI;
use xsrlRichTextBlockGUI;

/**
 * Class GUIProvider
 *
 * @package SRAG\Learnplaces\container\provider
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 */
final class GUIProvider implements ServiceProviderInterface {

	/**
	 * @inheritDoc
	 */
	public function register(Container $pimple) {
		$pimple[RenderableBlockViewFactory::class] = function ($c) {return new RenderableBlockViewFactoryImpl(); };

		$pimple[xsrlContentGUI::class] = function ($c) {return new xsrlContentGUI(
			$c['ilTabs'],
			$c['tpl'],
			$c['ilCtrl'],
			$c['ilAccess'],
			$c['http'],
			$c[ilLearnplacesPlugin::class],
			$c[RenderableBlockViewFactory::class],
			$c[LearnplaceService::class]
			);
		};

		$pimple[xsrlPictureUploadBlockGUI::class] = function ($c) {return new xsrlPictureUploadBlockGUI(
			$c['ilTabs'],
			$c['tpl'],
			$c['ilCtrl'],
			$c['ilAccess'],
			$c['http'],
			$c[ilLearnplacesPlugin::class],
			$c[PictureUploadBlockService::class],
			$c[LearnplaceService::class],
			$c[ConfigurationService::class]
		);
		};

		$pimple[xsrlPictureBlockGUI::class] = function ($c) {return new xsrlPictureBlockGUI(
			$c['ilTabs'],
			$c['tpl'],
			$c['ilCtrl'],
			$c['ilAccess'],
			$c['http'],
			$c[ilLearnplacesPlugin::class],
			$c[PictureService::class],
			$c[PictureBlockService::class],
			$c[LearnplaceService::class],
			$c[ConfigurationService::class]
		);
		};

		$pimple[xsrlRichTextBlockGUI::class] = function ($c) {return new xsrlRichTextBlockGUI(
			$c['ilTabs'],
			$c['tpl'],
			$c['ilCtrl'],
			$c['ilAccess'],
			$c['http'],
			$c[ilLearnplacesPlugin::class],
			$c[RichTextBlockService::class],
			$c[LearnplaceService::class],
			$c[ConfigurationService::class]
		);
		};
	}
}