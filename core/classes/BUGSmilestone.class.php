<?php

	/**
	 * Milestone class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage main
	 */

	/**
	 * Milestone class
	 *
	 * @package thebuggenie
	 * @subpackage main
	 */
	class BUGSmilestone extends BUGSidentifiableclass implements BUGSidentifiable  
	{
		/**
		 * This components project
		 *
		 * @var BUGSproject
		 */
		protected $_project;

		/**
		 * Whether the milestone has been reached
		 * 
		 * @var boolean
		 */
		protected $_isreached;
		
		/**
		 * When the milestone was reached
		 * 
		 * @var integer
		 */
		protected $_reacheddate;

		/**
		 * Whether the milestone has been scheduled for release
		 * 
		 * @var boolean
		 */
		protected $_isscheduled;
		
		/**
		 * When the milestone is scheduled for release
		 * 
		 * @var integer
		 */
		protected $_scheduleddate;
		
		/**
		 * The milestone description
		 * 
		 * @var string
		 */
		protected $_description;
		
		/**
		 * Internal cache of issues assigned
		 * 
		 * @var string
		 */
		protected $_issues = null;
		
		/**
		 * Number of closed issues
		 * 
		 * @var integer
		 */
		protected $_closed_issues;
		
		/**
		 * Get all milestones by a project id
		 * 
		 * @param integer $project_id The project id
		 * 
		 * @return array
		 */
		public static function getAllByProjectID($project_id)
		{
			$milestones = array();
			if ($res = B2DB::getTable('B2tMilestones')->getByProjectID($project_id))
			{
				while ($row = $res->getNextRow())
				{
					$milestone = BUGSfactory::milestoneLab($row->get(B2tMilestones::ID), $row);
					$milestones[$milestone->getID()] = $milestone; 
				}
			}
			return $milestones;
		}
		
		/**
		 * Create a new milestone and return it
		 * 
		 * @param string $name The milestone name
		 * @param integer $project_id The project id
		 * 
		 * @return BUGSmilestone
		 */
		static public function createNew($name, $project_id)
		{
			$m_id = B2DB::getTable('B2tMilestones')->createNew($name, $project_id);
			BUGScontext::setPermission('b2milestoneaccess', $m_id, 'core', 0, BUGScontext::getUser()->getGroup()->getID(), 0, true);
			return BUGSfactory::milestoneLab($m_id);
		}
		
		/**
		 * Constructor function
		 *
		 * @param integer $b_id The milestone id
		 * @param B2DBrow $row[optional] a database row with the necessary information if available
		 */
		public function __construct($m_id, $row = null)
		{
			if ($row === null)
			{
				$crit = new B2DBCriteria();
				$row = B2DB::getTable('B2tMilestones')->doSelectById($m_id, $crit);
			}
			
			if ($row instanceof B2DBRow)
			{
				$this->_name = $row->get(B2tMilestones::NAME);
				$this->_itemid = $row->get(B2tMilestones::ID);
				$this->_isvisible = (bool) $row->get(B2tMilestones::VISIBLE);
				$this->_isscheduled = (bool) $row->get(B2tMilestones::SCHEDULED);
				$this->_isreached = (bool) $row->get(B2tMilestones::REACHED);
				$this->_scheduleddate = $row->get(B2tMilestones::SCHEDULED);
				$this->_reacheddate = $row->get(B2tMilestones::REACHED);
				$this->_description = $row->get(B2tMilestones::DESCRIPTION);
				$this->_project = $row->get(B2tMilestones::PROJECT);
			}
			else
			{
				throw new Exception('This milestone does not exist');
			}
		}

		/**
		 * @see getName()
		 * @deprecated
		 */
		public function __toString()
		{
			throw new Exception("Don't print the object, use the getName() function instead");
		}
		
		/**
		 * Returns an array with issues
		 *
		 * @return array
		 */
		public function getIssues()
		{
			$this->_populateIssues();
			return $this->_issues;
		}
		
		/**
		 * Populates the internal array with issues
		 */
		protected function _populateIssues()
		{
			if ($this->_issues == null)
			{
				$this->_issues = array();
				if ($res = B2DB::getTable('B2tIssues')->getByMilestone($this->getID()))
				{
					while ($row = $res->getNextRow())
					{
						$theIssue = BUGSfactory::BUGSissueLab($row->get(B2tIssues::ID));
						$this->_issues[$theIssue->getID()] = $theIssue;
						if ($theIssue->getState() == BUGSissue::STATE_CLOSED)
						{
							$this->_closed_issues++;
						}
					}
				}
			}
		}

		/**
		 * Return the number of closed issues
		 * 
		 * @return integer
		 */
		public function getClosedIssues()
		{
			return $this->_closed_issues;
		}

		/**
		 * Set the milestone name
		 * 
		 * @param string $name The new name
		 */
		public function setName($name)
		{
			$this->_name = $name;
		}

		/**
		 * Get the description
		 * 
		 * @return string
		 */
		public function getDescription()
		{
			return $this->_description;
		}
		
		/**
		 * Set the milestone description
		 * 
		 * @param string $description The description
		 */
		public function setDescription($description)
		{
			$this->_description = $description;
		}

		/**
		 * Whether or not the milestone has been scheduled for release
		 * 
		 * @return boolean
		 */
		public function isScheduled()
		{
			return $this->_isscheduled;
		}
		
		/**
		 * Set whether or not the milestone is scheduled for release
		 * 
		 * @param boolean $scheduled[optional] scheduled or not (default true)
		 */
		public function setScheduled($scheduled = true)
		{
			$this->_isscheduled = $scheduled;
		}

		/**
		 * Return this milestones project
		 * 
		 * @return BUGSproject
		 */
		public function getProject()
		{
			if (is_numeric($this->_project))
			{
				try
				{
					$this->_project = BUGSfactory::projectLab($this->_project);
				}
				catch (Exception $e)
				{
					$this->_project = null;
				}
			}
			return $this->_project;
		}
		
		/**
		 * Whether this milestone has been reached or not
		 * 
		 * @return boolean
		 */
		public function isReached()
		{
			return $this->_isreached;
		}
		
		/**
		 * Whether or not this milestone is overdue
		 * 
		 * @return boolean
		 */
		public function isOverdue()
		{
			return ($this->getScheduledDate() < mktime(0, 0, 0, date('m'), date('d'), date('Y')) && !$this->isReached()) ? true : false;
		}
		
		/**
		 * Return when this milestone was reached
		 * 
		 * @return integer
		 */
		public function getReachedDate()
		{
			return $this->_reacheddate;
		}
		
		/**
		 * Return when this milestone is scheduled for release
		 * 
		 * @return integer
		 */
		public function getScheduledDate()
		{
			return $this->_scheduleddate;
		}
		
		/**
		 * Set this milestones scheduled release date
		 * 
		 * @param integer $date The timestamp for release
		 */
		public function setScheduledDate($date)
		{
			$this->_scheduleddate = $date;
			if ($date == 0)
			{
				$this->setScheduled(false);
			}
		}

		/**
		 * Return the year the milestone is scheduled for release
		 * 
		 * @return integer
		 */
		public function getScheduledYear()
		{
			return date("Y", $this->_scheduleddate);
		}
		
		/**
		 * Return the month the milestone is scheduled for release
		 * 
		 * @return integer
		 */
		public function getScheduledMonth()
		{
			return date("n", $this->_scheduleddate);
		}

		/**
		 * Return the day the milestone is scheduled for release
		 * 
		 * @return integer
		 */
		public function getScheduledDay()
		{
			return date("j", $this->_scheduleddate);
		}
		
		/**
		 * Return whether or not this milestone is visible
		 * 
		 * @return boolean
		 */
		public function isVisible()
		{
			return $this->_isvisible;
		}
		
		/**
		 * Return this milestones scheduled status, as an array
		 * 		array('color' => '#code', 'status' => 'description')
		 * 
		 * @return array
		 */
		public function getScheduledStatus()
		{
			if ($this->_isscheduled)
			{
				if ($this->_isreached == false)
				{
					if ($this->_scheduleddate < $_SERVER["REQUEST_TIME"])
					{
						for ($dcc = 1;$dcc <= 7;$dcc++)
						{
							if ($this->_scheduleddate > mktime(0, 0, 0, date('m'), date('d') - $dcc, date('Y')))
							{
								if ($dcc - 1 == 0)
								{
									return array('color' => 'D55', 'status' => 'This milestone is about a day late');
								}
								else
								{
									return array('color' => 'D55', 'status' => 'This milestone is ' . ($dcc - 1) . ' day(s) late');
								}
							}
						}
						for ($dcc = 1;$dcc <= 4;$dcc++)
						{
							if ($this->_scheduleddate > mktime(0, 0, 0, date('m'), date('d') - ($dcc * 7), date('Y')))
							{
								return array('color' => 'D55', 'status' => 'This milestone is about ' . $dcc . ' week(s) late');
							}
						}
						for ($dcc = 1;$dcc <= 12;$dcc++)
						{
							if ($this->_scheduleddate > mktime(0, 0, 0, date('m') - $dcc, date('d'), date('Y')))
							{
								return array('color' => 'D55', 'status' => 'This milestone is about ' . $dcc . ' month(s) late');
							}
						}
						return array('color' => 'D55', 'status' => 'This milestone is more than a year late');
					}
					else
					{
						for ($dcc = 0;$dcc <= 7;$dcc++)
						{
							if ($this->_scheduleddate < mktime(0, 0, 0, date('m'), date('d') + $dcc, date('Y')))
							{
								if ($dcc - 2 == 0)
								{
									return array('color' => '000', 'status' => 'This milestone is due today');
								}
								else
								{
									return array('color' => '000', 'status' => 'This milestone is scheduled for ' . ($dcc - 2) . ' days from today');
								}
							}
						}
						for ($dcc = 1;$dcc <= 4;$dcc++)
						{
							if ($this->_scheduleddate < mktime(0, 0, 0, date('m'), date('d') + ($dcc * 7), date('Y')))
							{
								return array('color' => '000', 'status' => 'This milestone is scheduled for ' . $dcc . ' week(s) from today');
							}
						}
						for ($dcc = 1;$dcc <= 12;$dcc++)
						{
							if ($this->_scheduleddate < mktime(0, 0, 0, date('m') + $dcc, date('d'), date('Y')))
							{
								return array('color' => '000', 'status' => 'This milestone is scheduled for ' . $dcc . ' month(s) from today');
							}
						}
						return array('color' => '000', 'status' => 'This milestone is scheduled for more than a year from today');
					}
				}
				elseif ($this->_reacheddate <= $this->_scheduleddate)
				{
					return array('color' => '3A3', 'status' => '<b>Reached: </b> ' . bugs_formatTime($this->_reacheddate, 6));
				}
				else
				{
					$ret_text = '<b>Reached: </b> ' . bugs_formatTime($this->_reacheddate, 6) . ', ';
					for ($dcc = 1;$dcc <= 7;$dcc++)
					{
						if ($this->_reacheddate < ($this->_scheduleddate + (86400 * $dcc)))
						{
							$ret_text .= '<b>' . ($dcc - 1) . ' day(s) late</b>';
							return array('color' => 'C33', 'status' => $ret_text);
						}
					}
					for ($dcc = 1;$dcc <= 4;$dcc++)
					{
						if ($this->_reacheddate < ($this->_scheduleddate + (604800 * $dcc)))
						{
							$ret_text .= '<b>about ' . ($dcc - 1) . ' week(s) late</b>';
							return array('color' => 'C33', 'status' => $ret_text);
						}
					}
					for ($dcc = 1;$dcc <= 12;$dcc++)
					{
						if ($this->_reacheddate < ($this->_scheduleddate + (2592000 * $dcc)))
						{
							$ret_text .= '<b>about ' . ($dcc - 1) . ' month(s) late</b>';
							return array('color' => 'C33', 'status' => $ret_text);
						}
					}
					$ret_text .= '<b>more than a year late</b>';
					return array('color' => 'C33', 'status' => $ret_text);
				}
			}
			else
			{
				return array('color' => '000', 'status' => '');
			}
		}
		
		/**
		 * Returns the milestones progress
		 * 
		 * @return integer
		 */
		public function getPercentComplete()
		{
			if (count($this->getIssues()) > 0)
			{
				$multiplier = 100 / count($this->getIssues());
				$pct = $this->_closed_issues * $multiplier;
			}
			else
			{
				$pct = 0;
			}
			return $pct;
		}
		
		/**
		 * Figure out this milestones status
		 */
		public function updateStatus()
		{
			if ($this->_issues == null)
			{
				$this->doPopulateIssues();
			}
			if ($this->_closed_issues == count($this->_issues))
			{
				B2DB::getTable('B2tMilestones')->setReached($this->getID());
			}
		}
		
		/**
		 * Save changes made to the milestone
		 */
		public function save()
		{
			$crit = new B2DBCriteria();
			$crit->addUpdate(B2tMilestones::NAME, $this->_name);
			$crit->addUpdate(B2tMilestones::DESCRIPTION, $this->_description);
			if ($this->_isscheduled)
			{
				$crit->addUpdate(B2tMilestones::SCHEDULED, $this->_scheduleddate);
			}
			else
			{
				$crit->addUpdate(B2tMilestones::SCHEDULED, 0);
			}
			$res = B2DB::getTable('B2tMilestones')->doUpdateById($crit, $this->getID());
		}

		/**
		 * Delete this milestone
		 */
		public function delete()
		{
			B2DB::getTable('B2tMilestones')->doDeleteById($this->getID());
			B2DB::getTable('B2tIssues')->clearMilestone($this->getID());
		}
		
		/**
		 * Whether or not the current user has access to this milestone
		 * 
		 * @return boolean
		 */
		public function hasAccess()
		{
			return BUGScontext::getUser()->hasPermission("b2milestoneaccess", $this->getID(), "core");			
		}
	}
	