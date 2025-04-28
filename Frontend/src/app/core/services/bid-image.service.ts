import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class BidImageService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  /**
   * Upload an image for a product bid
   * @param bidId - The ID of the bid to attach the image to
   * @param imageFile - The image file to upload
   * @param isPrimary - Whether this image should be set as the primary image
   */
  uploadImage(bidId: number, imageFile: File, isPrimary: boolean = false): Observable<any> {
    const formData = new FormData();
    formData.append('bid_id', bidId.toString());
    formData.append('image', imageFile);
    formData.append('is_primary', isPrimary.toString());

    return this.http.post(`${this.apiUrl}/bids/upload-image.php`, formData);
  }

  /**
   * Delete an image from a product bid
   * @param imageId - The ID of the image to delete
   */
  deleteImage(imageId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/bids/delete-image.php`, {
      body: { image_id: imageId }
    });
  }

  /**
   * Set an image as the primary image for a product bid
   * @param imageId - The ID of the image to set as primary
   */
  setPrimaryImage(imageId: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/bids/set-primary-image.php`, {
      image_id: imageId
    });
  }
}
