<?Php

namespace Resgef\SyncList\Lib\EbayApiFunctions;

class EbayApiFunctions
{
    /**
     * @param FixedPriceItem $Item
     */
    public function AddFixedPriceItem(FixedPriceItem $Item)
    {
        $requestXml =
            '<?xml version="1.0" encoding="utf-8"?>
            <AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
            <Item> ItemType
                <ApplicationData>local_id:' . $Item->local_id . '</ApplicationData>
                <AutoPay>True</AutoPay>
                <CategoryBasedAttributesPrefill>True</CategoryBasedAttributesPrefill>
                <CategoryMappingAllowed>True</CategoryMappingAllowed>   
                <ConditionDescription> string </ConditionDescription>
                <ConditionID> int </ConditionID>
                <Country> CountryCodeType </Country>
                <Currency> CurrencyCodeType </Currency>
                <Description> string </Description>
                <DispatchTimeMax> int </DispatchTimeMax>
                <eBayPlus>False</eBayPlus>
                <IncludeRecommendations>True</IncludeRecommendations>
                <ItemSpecifics> NameValueListArrayType
                  <NameValueList> NameValueListType
                    <Name> string </Name>
                    <Value> string </Value>
                    <!-- ... more Value values allowed here ... -->
                  </NameValueList>
                  <!-- ... more NameValueList nodes allowed here ... -->
                </ItemSpecifics>
                <ListingDuration> token </ListingDuration>
                <ListingType>FixedPriceItem</ListingType>
                <PaymentMethods> BuyerPaymentMethodCodeType </PaymentMethods>
                <PayPalEmailAddress> string </PayPalEmailAddress>
                <PictureDetails> PictureDetailsType
                  <GalleryDuration> token </GalleryDuration>
                  <GalleryType> GalleryTypeCodeType </GalleryType>
                  <PhotoDisplay> PhotoDisplayCodeType </PhotoDisplay>
                  <PictureSource> PictureSourceCodeType </PictureSource>
                  <PictureURL> anyURI </PictureURL>
                  <!-- ... more PictureURL values allowed here ... -->
                </PictureDetails>
                <PostalCode> string </PostalCode>
                <PrimaryCategory> CategoryType
                  <CategoryID> string </CategoryID>
                </PrimaryCategory>
                <PrivateListing>False</PrivateListing>
                <Quantity> int </Quantity>
                <ReturnPolicy> ReturnPolicyType
                  <Description> string </Description>
                  <ExtendedHolidayReturns> boolean </ExtendedHolidayReturns>
                  <RefundOption> token </RefundOption>
                  <RestockingFeeValueOption> token </RestockingFeeValueOption>
                  <ReturnsAcceptedOption> token </ReturnsAcceptedOption>
                  <ReturnsWithinOption> token </ReturnsWithinOption>
                  <ShippingCostPaidByOption> token </ShippingCostPaidByOption>
                  <WarrantyDurationOption> token </WarrantyDurationOption>
                  <WarrantyOfferedOption> token </WarrantyOfferedOption>
                  <WarrantyTypeOption> token </WarrantyTypeOption>
                </ReturnPolicy>
                <SecondaryCategory> CategoryType
                  <CategoryID> string </CategoryID>
                </SecondaryCategory>
                <SellerProfiles> SellerProfilesType
                  <SellerPaymentProfile> SellerPaymentProfileType
                    <PaymentProfileID> long </PaymentProfileID>
                    <PaymentProfileName> string </PaymentProfileName>
                  </SellerPaymentProfile>
                  <SellerReturnProfile> SellerReturnProfileType
                    <ReturnProfileID> long </ReturnProfileID>
                    <ReturnProfileName> string </ReturnProfileName>
                  </SellerReturnProfile>
                  <SellerShippingProfile> SellerShippingProfileType
                    <ShippingProfileID> long </ShippingProfileID>
                    <ShippingProfileName> string </ShippingProfileName>
                  </SellerShippingProfile>
                </SellerProfiles>
                <ShippingDetails> ShippingDetailsType
                  <CalculatedShippingRate> CalculatedShippingRateType
                    <InternationalPackagingHandlingCosts> AmountType (double) </InternationalPackagingHandlingCosts>
                    <MeasurementUnit> MeasurementSystemCodeType </MeasurementUnit>
                    <OriginatingPostalCode> string </OriginatingPostalCode>
                    <PackagingHandlingCosts> AmountType (double) </PackagingHandlingCosts>
                    <ShippingIrregular> boolean </ShippingIrregular>
                  </CalculatedShippingRate>
                  <CODCost> AmountType (double) </CODCost>
                  <ExcludeShipToLocation> string </ExcludeShipToLocation>
                  <!-- ... more ExcludeShipToLocation values allowed here ... -->
                  <GlobalShipping> boolean </GlobalShipping>
                  <InsuranceDetails> InsuranceDetailsType
                    <InsuranceFee> AmountType (double) </InsuranceFee>
                    <InsuranceOption> InsuranceOptionCodeType </InsuranceOption>
                  </InsuranceDetails>
                  <InternationalInsuranceDetails> InsuranceDetailsType
                    <InsuranceFee> AmountType (double) </InsuranceFee>
                    <InsuranceOption> InsuranceOptionCodeType </InsuranceOption>
                  </InternationalInsuranceDetails>
                  <InternationalPromotionalShippingDiscount> boolean </InternationalPromotionalShippingDiscount>
                  <InternationalShippingDiscountProfileID> string </InternationalShippingDiscountProfileID>
                  <InternationalShippingServiceOption> InternationalShippingServiceOptionsType
                    <ShippingService> token </ShippingService>
                    <ShippingServiceAdditionalCost> AmountType (double) </ShippingServiceAdditionalCost>
                    <ShippingServiceCost> AmountType (double) </ShippingServiceCost>
                    <ShippingServicePriority> int </ShippingServicePriority>
                    <ShipToLocation> string </ShipToLocation>
                    <!-- ... more ShipToLocation values allowed here ... -->
                  </InternationalShippingServiceOption>
                  <!-- ... more InternationalShippingServiceOption nodes allowed here ... -->
                  <PaymentInstructions> string </PaymentInstructions>
                  <PromotionalShippingDiscount> boolean </PromotionalShippingDiscount>
                  <RateTableDetails> RateTableDetailsType
                    <DomesticRateTable> string </DomesticRateTable>
                    <InternationalRateTable> string </InternationalRateTable>
                  </RateTableDetails>
                  <SalesTax> SalesTaxType
                    <SalesTaxPercent> float </SalesTaxPercent>
                    <SalesTaxState> string </SalesTaxState>
                    <ShippingIncludedInTax> boolean </ShippingIncludedInTax>
                  </SalesTax>
                  <ShippingDiscountProfileID> string </ShippingDiscountProfileID>
                  <ShippingServiceOptions> ShippingServiceOptionsType
                    <FreeShipping> boolean </FreeShipping>
                    <ShippingService> token </ShippingService>
                    <ShippingServiceAdditionalCost> AmountType (double) </ShippingServiceAdditionalCost>
                    <ShippingServiceCost> AmountType (double) </ShippingServiceCost>
                    <ShippingServicePriority> int </ShippingServicePriority>
                    <ShippingSurcharge> AmountType (double) </ShippingSurcharge>
                  </ShippingServiceOptions>
                  <!-- ... more ShippingServiceOptions nodes allowed here ... -->
                  <ShippingType> ShippingTypeCodeType </ShippingType>
                </ShippingDetails>
                <ShippingServiceCostOverrideList> ShippingServiceCostOverrideListType
                  <ShippingServiceCostOverride> ShippingServiceCostOverrideType
                    <ShippingServiceAdditionalCost> AmountType (double) </ShippingServiceAdditionalCost>
                    <ShippingServiceCost> AmountType (double) </ShippingServiceCost>
                    <ShippingServicePriority> int </ShippingServicePriority>
                    <ShippingServiceType> ShippingServiceType </ShippingServiceType>
                    <ShippingSurcharge> AmountType (double) </ShippingSurcharge>
                  </ShippingServiceCostOverride>
                  <!-- ... more ShippingServiceCostOverride nodes allowed here ... -->
                </ShippingServiceCostOverrideList>
                <ShippingTermsInDescription> boolean </ShippingTermsInDescription>
                <ShipToLocations> string </ShipToLocations>
                <!-- ... more ShipToLocations values allowed here ... -->
                <Site> SiteCodeType </Site>
                <SKU> SKUType (string) </SKU>
                <StartPrice> AmountType (double) </StartPrice>
                <SubTitle> string </SubTitle>
                <Title> string </Title>
                <UseTaxTable> boolean </UseTaxTable>
                <UUID> UUIDType (string) </UUID>
                <Variations> VariationsType
                  <Pictures> PicturesType
                    <VariationSpecificName> string </VariationSpecificName>
                    <VariationSpecificPictureSet> VariationSpecificPictureSetType
                      <PictureURL> anyURI </PictureURL>
                      <!-- ... more PictureURL values allowed here ... -->
                      <VariationSpecificValue> string </VariationSpecificValue>
                    </VariationSpecificPictureSet>
                    <!-- ... more VariationSpecificPictureSet nodes allowed here ... -->
                  </Pictures>
                  <Variation> VariationType
                    <DiscountPriceInfo> DiscountPriceInfoType
                      <MadeForOutletComparisonPrice> AmountType (double) </MadeForOutletComparisonPrice>
                      <MinimumAdvertisedPrice> AmountType (double) </MinimumAdvertisedPrice>
                      <MinimumAdvertisedPriceExposure> MinimumAdvertisedPriceExposureCodeType </MinimumAdvertisedPriceExposure>
                      <OriginalRetailPrice> AmountType (double) </OriginalRetailPrice>
                      <SoldOffeBay> boolean </SoldOffeBay>
                      <SoldOneBay> boolean </SoldOneBay>
                    </DiscountPriceInfo>
                    <Quantity> int </Quantity>
                    <SKU> SKUType (string) </SKU>
                    <StartPrice> AmountType (double) </StartPrice>
                    <VariationProductListingDetails> VariationProductListingDetailsType
                      <EAN> string </EAN>
                      <ISBN> string </ISBN>
                      <NameValueList> NameValueListType
                        <Name> string </Name>
                        <Value> string </Value>
                        <!-- ... more Value values allowed here ... -->
                      </NameValueList>
                      <!-- ... more NameValueList nodes allowed here ... -->
                      <UPC> string </UPC>
                    </VariationProductListingDetails>
                    <VariationSpecifics> NameValueListArrayType
                      <NameValueList> NameValueListType
                        <Name> string </Name>
                        <Value> string </Value>
                        <!-- ... more Value values allowed here ... -->
                      </NameValueList>
                      <!-- ... more NameValueList nodes allowed here ... -->
                    </VariationSpecifics>
                    <!-- ... more VariationSpecifics nodes allowed here ... -->
                  </Variation>
                  <!-- ... more Variation nodes allowed here ... -->
                  <VariationSpecificsSet> NameValueListArrayType
                    <NameValueList> NameValueListType
                      <Name> string </Name>
                      <Value> string </Value>
                      <!-- ... more Value values allowed here ... -->
                    </NameValueList>
                    <!-- ... more NameValueList nodes allowed here ... -->
                  </VariationSpecificsSet>
                </Variations>
                <VATDetails> VATDetailsType
                  <BusinessSeller> boolean </BusinessSeller>
                  <RestrictedToBusiness> boolean </RestrictedToBusiness>
                  <VATPercent> float </VATPercent>
                </VATDetails>
                <VIN> string </VIN>
                <VRM> string </VRM>
              </Item>
              <!-- Standard Input Fields -->
              <ErrorLanguage> string </ErrorLanguage>
              <MessageID> string </MessageID>
              <Version> string </Version>
              <WarningLevel> WarningLevelCodeType </WarningLevel>
            </AddFixedPriceItemRequest>';
    }
}