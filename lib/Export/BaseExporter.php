<?php

namespace LnBlog\Export;

use BadExport;
use Blog;
use BlogComment;
use BlogEntry;
use DateTime;
use DateTimeZone;
use GlobalFunctions;
use InvalidOption;
use LnBlog\Storage\UserRepository;

abstract class BaseExporter implements Exporter
{
    protected GlobalFunctions $globals;
    protected UserRepository $user_repo;

    private array $options = [];

    public static function prettyPrintXml(string $xml): string {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        $pretty_xml = $dom->saveXML();
        return $pretty_xml;
    }

    public function __construct(
        GlobalFunctions $globals = null,
        UserRepository $user_repo = null
    ) {
        $this->globals = $globals ?: new GlobalFunctions();
        $this->user_repo = $user_repo ?: new UserRepository();
    }

    abstract public function export($entity, ExportTarget $target): void;

    public function setExportOptions(array $options): void {
        $this->validateOptions($options);

        $this->options = $options;
    }

    public function getExportOptions(): array {
        return $this->options;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function validateOptions(array $options): void {
        throw new InvalidOption(_('No options are supported for this exporter'));
    }

    protected function getChannelData($channel): ChannelData {
        $data = new ChannelData();

        if ($channel instanceof Blog) {
            /** @var Blog $blog */
            $blog = $channel;
            $user = $this->user_repo->get($blog->owner);
            $data->class = Blog::class;
            $data->title = $blog->name;
            $data->description = $blog->description;
            $data->owner_name = $user->name() ?: $blog->owner;
            $data->owner_email = $user->email();
            $data->owner_url = $user->homepage();
            $data->permalink = $blog->getURL();
        } else {
            /** @var BlogEntry $entry */
            $entry = $channel;
            $user = $this->user_repo->get($entry->uid);
            $data->class = BlogEntry::class;
            $data->title = $entry->subject;
            $data->description = $entry->markup();
            $data->owner_name = $user->name();
            $data->owner_email = $user->email();
            $data->owner_url = $user->homepage();
            $data->permalink = $entry->permalink();
        }

        return $data;
    }

    protected function getItemData($item): ItemData {
        $data = new ItemData();

        $data->publish_date = new DateTime();
        $data->publish_date->setTimezone(new DateTimeZone('UTC'));
        $data->update_date = new DateTime();
        $data->update_date->setTimezone(new DateTimeZone('UTC'));

        if ($item instanceof BlogEntry) {
            /** @var BlogEntry $entry */
            $entry = $item;
            $user = $this->user_repo->get($entry->uid);
            $data->class = BlogEntry::class;
            $data->title = $entry->subject;
            $data->description = $entry->markup();
            $data->owner_name = $user->name();
            $data->owner_email = $user->email();
            $data->owner_url = $user->homepage();
            $data->permalink = $entry->permalink();
            $data->publish_date->setTimestamp($entry->post_ts);
            $data->update_date->setTimestamp($entry->timestamp ?: $entry->post_ts);
            $data->tags = $entry->tags();
            $data->comments_url = $entry->commentlink();
            $enclosure_data = $entry->getEnclosure();
            if ($enclosure_data) {
                $data->enclosure = $enclosure_data['url'];
                $data->enclosure_size = $enclosure_data['length'];
                $data->enclosure_type = $enclosure_data['type'];
            }
        } else {
            /** @var BlogComment $comment */
            $comment = $item;
            $data->class = BlogComment::class;
            $data->title = $comment->subject;
            $data->description = $comment->markup();
            $data->permalink = $comment->permalink();
            $data->publish_date->setTimestamp($comment->timestamp);
            $data->update_date->setTimestamp($comment->timestamp ?: $comment->post_ts);
            if ($comment->uid) {
                $user = $this->user_repo->get($comment->uid);
                $data->owner_name = $user->name();
                $data->owner_email = $user->email();
                $data->owner_url = $user->homepage();
            } else {
                $data->owner_name = $comment->name;
                $data->owner_email = $comment->email;
                $data->owner_url = $comment->url;
            }
        }

        return $data;
    }

    protected function getDefaultChildren($entity): array {
        if ($entity instanceof Blog) {
            return $entity->getEntries();
        } elseif ($entity instanceof BlogEntry) {
            return $entity->getComments();
        }
        throw new BadExport(_('Could not get default content'));
    }
}
