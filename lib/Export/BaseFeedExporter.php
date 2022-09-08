<?php

namespace LnBlog\Export;

use InvalidOption;

abstract class BaseFeedExporter extends BaseExporter
{
    public const OPTION_CHILDREN = 'children';
    public const OPTION_FEED_TITLE = 'title';
    public const OPTION_FEED_DESCRIPTION = 'description';
    public const OPTION_FEED_PERMALINK = 'permalink';

    protected function getValidOptions(): array {
        return [
            static::OPTION_CHILDREN,
            static::OPTION_FEED_DESCRIPTION,
            static::OPTION_FEED_PERMALINK,
            static::OPTION_FEED_TITLE,
        ];
    }

    protected function validateOptions(array $options): void {
        foreach ($options as $key => $val) {
            if (!in_array($key, $this->getValidOptions())) {
                throw new InvalidOption(spf_('Option "%s" is not supported', $key));
            }
        }

        $children = $options[static::OPTION_CHILDREN] ?? null;
        if ($children !== null && !is_array($children)) {
            throw new InvalidOption(spf_('Option "%s" must be an array', static::OPTION_CHILDREN));
        }
    }

    protected function applyChannelOverrides(ChannelData $data, array $options): void {
        $data->title = $options[self::OPTION_FEED_TITLE] ?? $data->title;
        $data->description = $options[self::OPTION_FEED_DESCRIPTION] ?? $data->description;
        $data->permalink = $options[self::OPTION_FEED_PERMALINK] ?? $data->permalink;
    }
}
