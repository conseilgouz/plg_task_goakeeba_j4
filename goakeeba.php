<?php
/** GoAkeeba
* Version			: 1.0.0
* Package			: Joomla 4.1
* copyright 		: Copyright (C) 2022 ConseilGouz. All rights reserved.
* license    		: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*
* lance akeebabackup                    
*/

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;

class PlgTaskGoAkeeba extends CMSPlugin implements SubscriberInterface
{
		use TaskPluginTrait;


	/**
	 * @var boolean
	 * @since 4.1.0
	 */
	protected $autoloadLanguage = true;
	/**
	 * @var string[]
	 *
	 * @since 4.1.0
	 */
	protected const TASKS_MAP = [
		'goakeeba' => [
			'langConstPrefix' => 'PLG_TASK_GOAKEEBA',
			'form'            => 'goakeeba',
			'method'          => 'goAkeeba',
		],
	];

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 4.1.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	protected function goAkeeba(ExecuteTaskEvent $event): int {
		$app = Factory::getApplication();
		$params = $event->getArgument('params');
		$profile = $params->profile;
		$pass = $params->pass;
		$uri = Uri::getInstance();
		$redirectUri = $uri::root() . 'index.php?option=com_akeebabackup&view=backup&profile='.$profile.'&key='.$pass;
		$mode = $params->mode;
		if ($mode == "redir") { // mode redirect
			$app->redirect($redirectUri);
			return TaskStatus::OK;
		} else { // mode curl
			$curl=curl_init();
			curl_setopt($curl,CURLOPT_URL,$redirectUri);
			curl_setopt($curl,CURLOPT_FOLLOWLOCATION,TRUE);
			curl_setopt($curl,CURLOPT_MAXREDIRS,10000); # Fix by Nicholas
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			$buffer = curl_exec($curl);
			curl_close($curl);
			if (empty($buffer)) {
				$res =  "Sorry, the backup didn't work.";
				return TaskStatus::NOTOK;
			} else {
				$res = $buffer;
				return TaskStatus::OK;
			}
		}
	}
}