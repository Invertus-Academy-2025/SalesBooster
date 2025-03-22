SalesBooster - AI sales analytics module.

! Remade repo because of git issues

Changelist in this version:
- Added configuration page for the module.
- Added the possibility to call symfony-app endpoint from backoffice.
- Added a template file to list out the response data and to choose which order dates should be fetched.

Additional set up:

If we want the containers to be able to see each other, we need to add them to the same network, otherwise calls from the backoffice to symfony-app's endpoints will fail:
- First, create a network: `docker network create salesbooster_network` (Run this once)
- Secondly, add these lines to the compose.yml file of both projects so they know to which network they need to attach:
```
networks:
    default:
        name: salesbooster_network
        external: true
 ```
