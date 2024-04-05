<?php

namespace Visualbuilder\EmailTemplates\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use Visualbuilder\EmailTemplates\Facades\TokenHelper;

trait BuildGenericEmail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = EmailTemplate::findEmailByKey($this->template, App::currentLocale());

        if($this->attachment ?? false) {
            $this->attach(
                $this->attachment->getPath(),
                [
                'as' => $this->attachment->filename,
                'mime' => $this->attachment->mime_type,
            ]
            );
        }


        // preparing logo
        $logo = $template->resolveLogoUrl($template->logo);

        $data = [
            'content' => TokenHelper::replace($template->content, $this),
            'preHeaderText' => TokenHelper::replace($template->preheader, $this),
            'title' => TokenHelper::replace($template->title, $this),
            'theme' => $template->theme->colours,
            'logo'  => $logo,
        ];

        return $this->from($template->from['email'], $template->from['name'])
            ->view($template->view_path)
            ->subject(TokenHelper::replace($template->subject, $this))
            ->to($this->sendTo)
            ->with(['data' => $data]);
    }
}
