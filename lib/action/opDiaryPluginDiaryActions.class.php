<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * diary actions.
 *
 * @package    OpenPNE
 * @subpackage diary
 * @author     Rimpei Ogawa <ogawa@tejimaya.com>
 */
class opDiaryPluginDiaryActions extends opDiaryPluginActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $this->forward('diary', 'list');
  }

  public function executeList(sfWebRequest $request)
  {
    $this->pager = Doctrine::getTable('Diary')->getDiaryPager($request->getParameter('page'), 20);
  }

  public function executeListMember(sfWebRequest $request)
  {
    $this->year  = (int)$request->getParameter('year');
    $this->month = (int)$request->getParameter('month');
    $this->day   = (int)$request->getParameter('day');

    if ($this->year && $this->month)
    {
      $this->forward404Unless(checkdate($this->month, ($this->day) ? $this->day : 1, $this->year), 'Invalid date format');
    }

    $this->pager = Doctrine::getTable('Diary')->getMemberDiaryPager($this->member->getId(), $request->getParameter('page'), 20, $this->getUser()->getMemberId(), $this->year, $this->month, $this->day);
  }

  public function executeListFriend(sfWebRequest $request)
  {
    $this->pager = Doctrine::getTable('Diary')->getFriendDiaryPager($this->getUser()->getMemberId(), $request->getParameter('page'), 20);
  }

  public function executeShow(sfWebRequest $request)
  {
    $this->forward404Unless($this->isDiaryViewable());

    if ($this->isDiaryAuthor())
    {
      Doctrine::getTable('DiaryCommentUnread')->unregister($this->diary);
    }

    $this->form = new DiaryCommentForm();
  }

  public function executeNew(sfWebRequest $request)
  {
    $this->form = new DiaryForm();
  }

  public function executeCreate(sfWebRequest $request)
  {
    $this->form = new DiaryForm();
    $this->form->getObject()->setMemberId($this->getUser()->getMemberId());
    $this->processForm($request, $this->form);
    $this->setTemplate('new');
  }

  public function executeEdit(sfWebRequest $request)
  {
    $this->forward404Unless($this->isDiaryAuthor());

    $this->form = new DiaryForm($this->diary);
  }

  public function executeUpdate(sfWebRequest $request)
  {
    $this->forward404Unless($this->isDiaryAuthor());

    $this->form = new DiaryForm($this->diary);
    $this->processForm($request, $this->form);
    $this->setTemplate('edit');
  }

  public function executeDeleteConfirm(sfWebRequest $request)
  {
    $this->forward404Unless($this->isDiaryAuthor());

    $this->form = new sfForm();
  }

  public function executeDelete(sfWebRequest $request)
  {
    $this->forward404Unless($this->isDiaryAuthor());
    $request->checkCSRFProtection();

    $this->diary->delete();

    $this->getUser()->setFlash('notice', 'The diary was deleted successfully.');

    $this->redirect('@diary_list_member?id='.$this->getUser()->getMemberId());
  }

  protected function processForm(sfWebRequest $request, sfForm $form)
  {
    $form->bind(
      $request->getParameter($form->getName()),
      $request->getFiles($form->getName())
    );

    if ($form->isValid())
    {
      $diary = $form->save();

      $this->redirect('@diary_show?id='.$diary->getId());
    }
  }
}
