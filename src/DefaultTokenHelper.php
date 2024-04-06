<?php

namespace Visualbuilder\EmailTemplates;


use Illuminate\Support\Facades\View;
use Visualbuilder\EmailTemplates\Contracts\TokenReplacementInterface;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;


class DefaultTokenHelper implements TokenReplacementInterface
{
    /**
     * Replace tokens in the content with actual values from the models.
     *
     * @param  string  $content  The content with tokens to be replaced
     * @param  array  $models  The models containing the values for the tokens
     *
     * @return string The content with replaced tokens
     */
    public function replaceTokens(string $content, $models): string
    {
        // Replace singular tokens.
        // These are for password reset and email verification

        if (isset($models->tokenUrl)) {
            $content = str_replace('##tokenURL##', $models->tokenUrl, $content);
        }

        if (isset($models->verificationUrl)) {
            $content = str_replace('##verificationUrl##', $models->verificationUrl, $content);
        }

        if (isset($models->message)) {
            $content = str_replace('##message##', $models->message, $content);
        }

        /**
         * Replace model-attribute tokens.
         * Will look for pattern ##model.attribute## and replace the value if found.
         * Eg ##user.name## or create your own accessors in a model
         */
        preg_match_all('/##(.*?)\.(.*?)##/', $content, $matches);

        if (count($matches) > 0 && count($matches[0]) > 0) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $modelKey = $matches[1][$i];
                $attributeKey = $matches[2][$i];

                if (isset($models->$modelKey) && isset($models->$modelKey->$attributeKey)) {
                    $content = str_replace($matches[0][$i], $models->$modelKey->$attributeKey, $content);
                }
            }
        }

        /**
         * User Consent Email tokens
         */
        if(isset($models->consentOptions)){
            $content =  str_replace('##consent-options##',view('vendor.user-consent.mails.accept-notification', ['consentOptions' => $models->consentOptions])->render(),$content);


            $coachingContract = $models->consentOptions->filter(function ($option) {
                return $option->key == 'coaching-contract';
            });

            if($coachingContract && $models->user instanceof EndUser)
            {
                if($order = $models->user->latestOrderWithCategory('Contract'))
                {
                    $totalCoachingHours = $order->total_coaching_duration;
                    $expectedWeeks = estimateCoachingWeeks($totalCoachingHours);
                    $content  = str_replace('{{ total_hours_coaching }}', $totalCoachingHours,$content);
                    $content  = str_replace('{{ expected_weeks }}', $expectedMonths,$content);
                }


            }

        }

        // Replace config tokens.
        $allowedConfigKeys = config('filament-email-templates.config_keys');

        preg_match_all('/##config\.(.*?)##/', $content, $matches);
        if (count($matches) > 0 && count($matches[0]) > 0) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $configKey = $matches[1][$i];
                if (in_array($configKey, $allowedConfigKeys)) {
                    $configValue = config($configKey);
                    if ($configValue !== null) {
                        $content = str_replace($matches[0][$i], $configValue, $content);
                    }
                }
            }
        }

        if(isset($models->emailTemplate)){
            $button = $this->buildEmailButton($content, $models->emailTemplate);
            $content = self::replaceButtonToken($content, $button);
        }


        return $content;
    }

    private function buildEmailButton($content, $emailTemplate)
    {
        $title = $url = '';
        if (preg_match('/(?<=##button).*?(?=#)/', $content, $matches)) {
            if ($check1 = preg_match("/(?<=url=').*?(?='\s)/", $matches[ 0 ], $url)) {
                $url = $url[ 0 ];
            }
            if ($check2 = preg_match("/(?<=title=').*?(?=')/", $matches[ 0 ], $title)) {
                $title = $title[ 0 ];
            }
            if ($check1 && $check2) {

                return View::make('vb-email-templates::email.parts._button', [
                    'url' => $url,
                    'title' => $title,
                    'data' => ['theme' => $emailTemplate->theme->colours],
                ])
                    ->render();
            }
        };

        return '';
    }

    private static function replaceButtonToken($content, $button)
    {
        $search = "/(?<=##button).*?(?=##)/";
        $replace = "";
        $content = preg_replace($search, $replace, $content);
        $content = str_replace('##button##', $button, $content);

        return $content;
    }
}
