<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;
use CleaniqueCoders\Shrinkr\Models\Url;
use Throwable;

/**
 * Class DeleteShortUrlAction
 *
 * Handles the deletion of a shortened URL.
 */
class DeleteShortUrlAction
{
    /**
     * Executes the action to delete a given URL.
     *
     * @param  Url  $url  The URL model instance to be deleted.
     * @return bool True if deletion was successful.
     *
     * @throws ShrinkrException If an error occurs during deletion.
     */
    public function execute(Url $url): bool
    {
        try {
            $url->delete();

            return true;
        } catch (Throwable $th) {
            // Wrap and rethrow any throwable as a ShrinkrException
            throw new ShrinkrException($th->getMessage(), 0, $th);
        }
    }
}
