TWCDataBundle
=============

Symfony2 bundle for working with The Weather Channel API

Introduction
============

This Bundle enables integration The Weather Channel API.

>**Note:** Only `/data/...` resource api

Installation
============

  1. Add this bundle and kriswallsmith Buzz library to your project as Git submodules:

          $ git submodule add git://github.com/kriswallsmith/Buzz.git vendor/buzz
          $ git submodule add git://github.com/suncat2000/TWCDataBundle.git vendor/bundles/SunCat/TWCDataBundle

  2. Register the namespace `Buzz` & `SunCat` to your project's autoloader bootstrap script:

          //app/autoload.php
          $loader->registerNamespaces(array(
                // ...
                'Buzz'      => __DIR__.'/../vendor/buzz/lib',
                'SunCat'    => __DIR__.'/../vendor/bundles',
                // ...
          ));

  3. Add this bundle to your application's kernel:

          //app/AppKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new SunCat\TWCDataBundle\TWCDataBundle(),
                  // ...
              );
          }

  4. Configure the `twc_data` service in your YAML configuration:

            #app/config/config.yml
            twc_data:
                apikey: your_api_key
                format: json        # or xml; optional, default: json
                units: m            # or s; optional, default: m
                locale: en_GB       # optional, default: en_GB
                country: UK         # optional, default: UK

Usage example
============

``` php

<?php
// src/Acme/YourBundle/Command/LocSearchCommand.php

        class LocSearchCommand extends ContainerAwareCommand
        {
            /**
             * @see Command
             */
            protected function configure()
            {
                $this
                    ->setName('wheather:locsearch')
                    ->setDescription('Get location ID of city by city name')
                    ->addOption('city', 0, InputOption::VALUE_REQUIRED, 'City name')
                    ->setHelp(<<<EOF
        The <info>wheather:locsearch</info> command get location ID.
        
        <info>php app/console wheather:locsearch --city=london</info>
        
        EOF
                    )
                ;
            }

            /**
             * {@inheritdoc}
             */
            protected function execute(InputInterface $input, OutputInterface $output)
            {
                $twcData = $this->getContainer()->get('twc_data.api_data');
                $twcData->setCommand('locsearch');
                $cityName = $input->getOption('city');

                if(!$cityName){
                    throw new \Exception('Enter city name');
                }

                $twcData->setResourcePart($cityName);
                $response = $twcData->getData();

                if($twcData->getFormat() == 'json'){
                    $data = json_decode($response->getContent());
                    //
                    // put your code
                    //
                }
            }

        }
```

For set query string parameters:

``` php
        $twcData->setParams(array('day' => '0,1,2'));
        $twcData->setParams(array('start' => 1293840000, 'end' => 1296518399));
```

The Weather Channel® API Implementation Guide
============

[Support & Download](http://portal.theweatherchannel.com/support.aspx)