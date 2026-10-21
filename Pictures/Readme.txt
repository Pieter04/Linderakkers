First build the docker image to install all perl and node modules
$ docker-compose up --build

#$ docker-compose up -d
#$ docker exec -i -t nodepictureparser_container /Pictures/parsfiles.pl
#$ docker-compose down


# Create all images for directories where conver.jpg is 
# missing in the subdirecoory thumbs or images
# If the dir org_images contains a conver.jpg file this will 
# be the cover.json in the other dirs. otherwise a random image is token

#$ docker-compose run --rm nodepictureparser /Pictures/parsfiles.pl