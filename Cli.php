<?php

namespace Ioncube\Di;

class Cli
{
    protected $excludePatterns = [];

    /**
     * @param string $path
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createDependencies($path)
    {
        $realPath = realpath($path);
        $recursiveIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realPath)
        );

        $classes = [];
        $precompile = [];
        foreach ($recursiveIterator as $fileItem) {
            /** @var $fileItem \SplFileInfo */
            if ($fileItem->isDir() ||
                $fileItem->getExtension() !== 'php' ||
                preg_match('/vendor/', $fileItem->getRealPath())) {
                continue;
            }
            $fileScanner = new \Zend\Code\Scanner\FileScanner($fileItem->getRealPath());
            $classNames = $fileScanner->getClassNames();
            foreach ($classNames as $className) {
                $class = new \ReflectionClass($className);
                $classes[] = $className;
                $constructor = $class->getConstructor();
                if (!$constructor || $constructor->class !== $className) {
                    continue;
                }

                foreach ($constructor->getParameters() as $param) {
                    $precompile[$className][] = [
                      $param->getName(),
                      $this->getClassName($param) !== null ? $this->getClassName($param) : null,
                      !$param->isOptional(),
                      $param->isOptional()
                      ? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null)
                      : null,
                    ];
                }
            }
        }
        $etcPath = $path.DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR;
        file_put_contents($etcPath.'classmap.csv', implode(':', $classes));
        if (!count(array_keys($precompile))) {
            return;
        }
        file_put_contents($etcPath.'deps.json', json_encode($precompile));
    }

    private function getClassName(\ReflectionParameter $param)
    {
        preg_match('/\[\s\<\w+?>\s([\w\\\\]+)/s', $param->__toString(), $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }
}
