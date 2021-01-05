<?php namespace NumenCode\Fundamentals\Classes;

class ImageResizer
{
    public function makeResizedUrls($content)
    {
        preg_match_all('/<img[^>]+>/i', $content, $imageTags);

        foreach ($imageTags[0] as $imageTag) {
            if (!stristr($imageTag, '/storage/app/media/')) {
                continue;
            }

            preg_match('/src="([^"]+)" style="width: ([^;]+)/i', $imageTag, $imageMatch);

            if (!isset($imageMatch[1]) && !isset($imageMatch[2])) {
                continue;
            }
            $imagePath = $imageMatch[1];
            $imageWidth = str_replace('px', '', $imageMatch[2]);
            $imageName = basename($imagePath);

            $newPath = str_replace($imageName, '', $imagePath) . 'thumb/'. $imageWidth .'x0.crop/' . $imageName;
            $content = str_replace($imagePath, $newPath, $content);
        }

        return $content;
    }
}
