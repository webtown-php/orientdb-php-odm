<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\CMS;

/**
 * @Document(oclass="CmsArticle")
 */
class CmsArticle
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Version
     */
    public $version;

    /**
     * @Property(type="string", max=255)
     */
    public $topic;
    /**
     * @Property(type="string")
     */
    public $text;
    /**
     * @Link(targetDoc="CmsUser")
     */
    public $user;
    /**
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    //public $comments;


    public function setAuthor(CmsUser $author) {
        $this->user = $author;
    }

//    public function addComment(CmsComment $comment) {
//        $this->comments[] = $comment;
//        $comment->setArticle($this);
//    }
}
