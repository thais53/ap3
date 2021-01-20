<?php

namespace App\Traits;

use App\Models\Document;

trait StoresDocuments
{
    /**
     * Associates all documents with a given token to a model.
     */
    protected function storeDocumentsByToken($item, $token, $company)
    {
        if ($token && $item) {
            $documents = Document::where('token', $token)->get();

            foreach ($documents as $doc) {
                $doc->moveFile($company->name . '/' . $item->getTable());
                $doc->token = null;
                $doc->save();
                $item->documents()->attach($doc->id, ['model' => get_class($item)]);
            }
        }
    }

    /**
     * Removes all other documents from item and deletes them if unused.
     */
    protected function deleteUnusedDocuments($item, $documentIds)
    {
        $documents = $item->documents()->whereNotIn('id', array_map(function ($doc) {
            return $doc['id'];
        }, $documentIds))->get();

        foreach ($documents as $doc) {
            $item->documents()->detach($doc->id);
            if (!$doc->has_models) {
                $doc->deleteFile();
            }
        }
    }
}
