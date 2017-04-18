<?php

namespace Ioncube\Di;

class Cli {
  protected $excludePatterns = [];

  public function createDependencies($path)
  {
    $realPath = realpath($path);
    $recursiveIterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($realPath)
    );

    $classes = [];
    foreach ($recursiveIterator as $fileItem) {
        /** @var $fileItem \SplFileInfo */
        if ($fileItem->isDir() || $fileItem->getExtension() !== 'php' || preg_match('/vendor/', $fileItem->getRealPath())) {
            continue;
        }
        $fileScanner = new \Zend\Code\Scanner\FileScanner($fileItem->getRealPath());
        $classNames = $fileScanner->getClassNames();
        foreach ($classNames as $className) {
            $class = new \ReflectionClass($className);
            $classes[$className] = [];
            $constructor = $class->getConstructor();
            if(!$constructor || $constructor->class !== $className) {
              continue;
            }

            foreach ($constructor->getParameters() as $parameter) {
              $classes[$className][] = [
                  $parameter->getName(),
                  $this->getClassName($parameter) !== null ? $this->getClassName($parameter) : null,
                  !$parameter->isOptional(),
                  $parameter->isOptional()
                      ? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null)
                      : null,
              ];
            }
        }
    }
  }


  private function getClassName(\ReflectionParameter $param) {
    preg_match('/\[\s\<\w+?>\s([\w\\\\]+)/s', $param->__toString(), $matches);
    return isset($matches[1]) ? $matches[1] : null;
  }
}
