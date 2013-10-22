<?php

namespace Flying\Bundle\ClientActionBundle\ClientAction;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Client action for "load" action
 *
 * @property string $url    URL to load information from
 *
 * @Struct\Enum(name="action", values={"load"}, default="load", nullable=false)
 * @Struct\String(name="url", nullable=true)
 */
class LoadClientAction extends StateAwareClientAction
{
    /**
     * {@inheritdoc}
     */
    public function __construct($ca = null, $config = null)
    {
        if ($config instanceof UrlGeneratorInterface) {
            $config = array('url_generator' => $config);
        }
        parent::__construct($ca, $config);
    }

    /**
     * {@inheritdoc}
     */
    protected function actionToString()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function toClient()
    {
        $client = parent::toClient();
        if (strpos($client['url'], '/') === false) {
            // Render route name into URL
            /** @var $generator UrlGeneratorInterface */
            $generator = $this->getConfig('url_generator');
            if (!$generator) {
                throw new \RuntimeException('URL generator service should be provided to allow handling routes in "load" client actions');
            }
            $args = array();
            if (array_key_exists('args', $client)) {
                $args = $client['args'];
                unset($client['args']);
            };
            $client['url'] = $generator->generate($client['url'], $args);
        }
        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return (parent::isValid() && (boolean)strlen($this->url));
    }

    /**
     * {@inheritdoc}
     */
    protected function postParse($parts)
    {
        $parts = parent::postParse($parts);
        if ($parts['action'] == 'load') {
            if ((!strlen($parts['url'])) &&
                (array_key_exists('contents', $parts)) && (strlen($parts['contents']))
            ) {
                $parts['url'] = $parts['contents'];
            }
            unset($parts['contents']);
        }
        return $parts;
    }

    /**
     * {@inheritdoc}
     */
    protected function initConfig()
    {
        parent::initConfig();
        $this->mergeConfig(array(
            'url_generator' => null, // URL generator to use to generate URLs by given route names
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfig($name, &$value)
    {
        switch ($name) {
            case 'url_generator':
                if (($value !== null) && (!$value instanceof UrlGeneratorInterface)) {
                    throw new \InvalidArgumentException('URL generator object must be instance of UrlGeneratorInterface');
                }
                break;
            default:
                return parent::validateConfig($name, $value);
                break;
        }
        return true;
    }
}
