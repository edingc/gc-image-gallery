== Google Cloud Image Gallery ==

All commands executured through a Google Cloud Shell.

First, create an environment variable with the same of your project ID:

export PROJECT_ID=edingc-image-gallery

Create a new Google Cloud project and set it as the active project in Cloud Shell.

gcloud projects create ${PROJECT_ID}
gcloud config set project ${PROJECT_ID}

Clone this repository to the Cloud Shell:

git clone 
