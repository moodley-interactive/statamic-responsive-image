<?php

namespace Valschr\ImageRenderer\Tags;

use Statamic\Tags\Tags;

class ResponsiveImageTag extends Tags
{
  protected static $handle = 'resp';

  public function index()
  {
      ray($this->params);
      return null;
  }

  public function wildcard($tag)
  {
      $this->params->put('src', $this->context->get($tag));

      return $this->index();
  }
}
